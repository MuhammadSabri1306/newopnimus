const path = require("path");
const fs = require("fs");
const { convertFile } = require("convert-svg-to-png");

(async () => {

    const inputPath = path.resolve(__dirname, "../public/checkport_chart_port33680.svg");
    const ouputPath = await convertFile(inputPath, {
        width: 900,
        height: 600
    });
    console.log(ouputPath);

})();