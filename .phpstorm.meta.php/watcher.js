const fs = require('node:fs');

const META_FILE = ".phpstorm.meta.php/extra.meta.php";

function readFile(pFileName){
    return new Promise((resolve, reject)=> {
            fs.readFile(pFileName, 'UTF-8', (pError, pData) => {
                if(pError){
                    return reject(pError);
                }
                if(pFileName.indexOf(".json")>-1){
                    pData = JSON.parse(pData);
                }
                resolve(pData);
            });
        }
    );
}

function savePhpStormMeta(pIdentifier, pContent){
    readFile(META_FILE).then((pMetas)=>{
        let re = new RegExp(`\\/\\*${pIdentifier}\\*\\/(.*)\\/\\*${pIdentifier}\\*\\/`, 'i');
        pMetas = pMetas.replace(re, '/*'+pIdentifier+'*/'+pContent+'/*'+pIdentifier+'*/');
        fs.writeFile(META_FILE, pMetas, (err)=>{
            if(err){
                console.log("nop");
            }
        });
    });
}

module.exports = {readFile, savePhpStormMeta};