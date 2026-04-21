const {readFile, savePhpStormMeta} = require('./watcher');

readFile('includes/components/manifest.json').then((pData)=>{
    let params = [];
    for(let i in pData){
        if(!pData.hasOwnProperty(i) || i === "config"){
            continue;
        }
        params.push('"'+i+'"');
    }
    params.sort();
    savePhpStormMeta("CL", 'registerArgumentsSet(\'ComponentsList\', '+params.join(',')+');');
});