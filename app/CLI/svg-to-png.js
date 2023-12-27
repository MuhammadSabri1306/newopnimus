const path = require("path");
const fs = require("fs");
const { program } = require("commander");
const { convertFile } = require("convert-svg-to-png");

program.parse();

// const options = program.opts();
const inputPath = path.resolve(__dirname, "../../public", program.args[0]);
console.log(inputPath);

(async () => {

    const ouputPath = await convertFile(inputPath, {
        width: 900,
        height: 600
    });
    console.log(ouputPath);

})();