const fs = require("fs/promises");
const path = require("path");

const dataDir = path.join(__dirname, "..", "data");

async function readJson(fileName, fallback = []) {
  const filePath = path.join(dataDir, fileName);
  const content = await fs.readFile(filePath, "utf8");
  try {
    return JSON.parse(content);
  } catch (error) {
    return fallback;
  }
}

async function writeJson(fileName, payload) {
  const filePath = path.join(dataDir, fileName);
  await fs.writeFile(filePath, JSON.stringify(payload, null, 2), "utf8");
}

module.exports = {
  readJson,
  writeJson,
};
