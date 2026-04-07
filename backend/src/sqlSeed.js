const fs = require("fs/promises");
const path = require("path");
const { writeJson } = require("./storage");

const sqlPath = path.join(__dirname, "..", "..", "database", "database.sql");

function splitTopLevelTuples(valuesText) {
  const tuples = [];
  let current = "";
  let depth = 0;
  let inString = false;

  for (let i = 0; i < valuesText.length; i += 1) {
    const ch = valuesText[i];
    const next = valuesText[i + 1];

    current += ch;

    if (ch === "'" && inString && next === "'") {
      current += next;
      i += 1;
      continue;
    }

    if (ch === "'") {
      inString = !inString;
      continue;
    }

    if (!inString) {
      if (ch === "(") depth += 1;
      if (ch === ")") depth -= 1;
      if (ch === "," && depth === 0) {
        tuples.push(current.slice(0, -1).trim());
        current = "";
      }
    }
  }

  if (current.trim()) tuples.push(current.trim());
  return tuples;
}

function splitTupleFields(tupleText) {
  const raw = tupleText.trim().replace(/^\(/, "").replace(/\)$/, "");
  const fields = [];
  let current = "";
  let inString = false;

  for (let i = 0; i < raw.length; i += 1) {
    const ch = raw[i];
    const next = raw[i + 1];

    if (ch === "'" && inString && next === "'") {
      current += "''";
      i += 1;
      continue;
    }

    if (ch === "'") {
      inString = !inString;
      current += ch;
      continue;
    }

    if (ch === "," && !inString) {
      fields.push(current.trim());
      current = "";
      continue;
    }

    current += ch;
  }

  if (current.trim()) fields.push(current.trim());
  return fields;
}

function unquoteSqlString(value) {
  if (!value) return "";
  const normalized = value.replace(/^N'/, "'").trim();
  if (!normalized.startsWith("'")) return normalized;
  return normalized.slice(1, -1).replace(/''/g, "'");
}

function slugify(value) {
  return value
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

async function seedToursFromSql() {
  const sql = await fs.readFile(sqlPath, "utf8");

  const destinationMatch = sql.match(
    /INSERT INTO dbo\.Destinations[\s\S]*?VALUES\s*([\s\S]*?);/m,
  );
  const tourMatch = sql.match(
    /INSERT INTO dbo\.Tours[\s\S]*?VALUES\s*([\s\S]*?)\n\s*--/m,
  );

  if (!destinationMatch || !tourMatch) {
    throw new Error("Không đọc được dữ liệu Destinations/Tours từ database.sql");
  }

  const destinationTuples = splitTopLevelTuples(destinationMatch[1]);
  const destinations = destinationTuples.map((tuple, index) => {
    const fields = splitTupleFields(tuple);
    const name = unquoteSqlString(fields[0]);
    return {
      id: index + 1,
      name,
      slug: slugify(name),
    };
  });

  const toursTuples = splitTopLevelTuples(tourMatch[1]).filter((item) =>
    item.trim().startsWith("("),
  );
  const tours = toursTuples.map((tuple, idx) => {
    const fields = splitTupleFields(tuple);
    const code = unquoteSqlString(fields[0]);
    const name = unquoteSqlString(fields[1]);
    const destinationId = Number(fields[4]);
    const durationDays = Number(fields[5]);
    const price = Number(fields[9]);
    const isInternational = Number(fields[18]) === 1;
    const tourType = unquoteSqlString(fields[20] || "");
    const destination = destinations.find((d) => d.id === destinationId);

    return {
      id: `tour-${idx + 1}`,
      code,
      name,
      price,
      duration: `${durationDays} ngày`,
      destination: destination ? destination.slug : "unknown",
      destinationName: destination ? destination.name : "Unknown",
      type: slugify(tourType || (isInternational ? "international" : "domestic")),
      rating: 4.5,
    };
  });

  await writeJson("tours.json", tours);
  return tours;
}

module.exports = {
  seedToursFromSql,
};
