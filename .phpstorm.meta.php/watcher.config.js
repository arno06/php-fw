const {readFile, savePhpStormMeta} = require('./watcher');

function extractKeys(pObject, pPath = '', pKeys = []){
    for(let i in pObject){
        if(!pObject.hasOwnProperty(i)){
            continue;
        }
        let k = i;
        if(pPath.length>0){
            k = pPath+'.'+i;
        }
        if(pKeys.indexOf(k) === -1){
            pKeys.push(k);
        }
        if(typeof pObject[i] !== "object"){
            continue;
        }
        extractKeys(pObject[i], k, pKeys);
    }
}

readFile('includes/applications/setup.json').then(async (pSetup)=>{
    let keys = [];
    for(let i in pSetup){
        if(!pSetup.hasOwnProperty(i)){
            continue;
        }
        let conf = await readFile("includes/applications/"+i+".config.json");
        extractKeys(conf.extra||{}, '', keys);
    }
    keys.sort();
    savePhpStormMeta("CF", 'registerArgumentsSet(\'ExtraList\', "'+keys.join('","')+'");');
});