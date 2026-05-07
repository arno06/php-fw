/**
 * JS Async Uploader
 * @author Arnaud NICOLAS <arno06@gmail.com>
 * @version 1.0
 */
const Uploader = (function(pDict){
    "use strict";

    let dict = pDict||{
        "seeFile":"Voir le fichier"
    };

    let states = {
        LOADING:"loading",
        IDLE:"",
        HAS_FILE:"has_file"
    };

    function init()
    {
        document.querySelectorAll('input[type="file"]').forEach(function(input)
        {
            setupContext(input);
            input.dataset.progress = "0";
            input.addEventListener("change", fileChangedHandler, false);
            let dropZone = input.parentNode;
            dropZone.addEventListener('drop', dropHandler);
            dropZone.addEventListener('dragover', dragHandler);
            dropZone.addEventListener('dragleave', dragLeaveHandler);
        });
        document.querySelectorAll('a.file+a.delete').forEach(function(a)
        {
            a.addEventListener('click', deleteCurrentFileHandler, false);
        });
    }

    function dragLeaveHandler(e){
        e.currentTarget.classList.remove('droppable');
    }

    function dropHandler(e){
        e.preventDefault();
        e.currentTarget.classList.remove('droppable');
        uploadFile(e.currentTarget.querySelector('input[type="file"]'), e.dataTransfer.files[0]);
    }

    function dragHandler(e){
        e.preventDefault();
        e.currentTarget.classList.add('droppable');
    }

    function setupContext(pInput)
    {
        const name = pInput.dataset.form_name+'['+pInput.dataset.input_name+']';
        const parent = pInput.parentNode;

        const hasFile = pInput.dataset.file && pInput.dataset.value;

        const progrDiv = document.createElement('div');
        progrDiv.classList.add('status_bar');
        progrDiv.appendChild(document.createElement('div')).classList.add('background');
        progrDiv.appendChild(document.createElement('div')).classList.add('foreground');
        progrDiv.appendChild(document.createElement('span'));
        parent.appendChild(progrDiv);

        const iHidden = document.createElement('input');
        iHidden.setAttribute('name', name);
        iHidden.setAttribute('type', 'hidden');
        iHidden.setAttribute('value', '');
        parent.appendChild(iHidden);

        const aFile = document.createElement("a");
        aFile.classList.add('file');
        aFile.innerHTML = dict.seeFile;
        aFile.setAttribute('target', '_blank');
        aFile.setAttribute('href', "#");
        aFile.appendChild(document.createElement('span'));
        parent.appendChild(aFile);

        const aDelete = document.createElement("a");
        aDelete.classList.add('delete');
        aDelete.setAttribute('href', '#');
        aDelete.appendChild(document.createElement('span'));
        parent.appendChild(aDelete);

        if(hasFile)
            uploadCompleted(pInput, {path_upload:pInput.dataset.file, id_upload:pInput.dataset.value});
    }

    function deleteCurrentFileHandler(e)
    {
        e.stopPropagation();
        e.stopImmediatePropagation();
        e.preventDefault();
        resetUploadInput(e.currentTarget.parentNode.querySelector('input[type="file"]'));
    }

    function fileChangedHandler(e)
    {
        uploadFile(e.currentTarget, e.currentTarget.files[0]);
    }

    function uploadFile(pInput, pFile)
    {
        pInput.parentNode.className = "input upload "+states.LOADING;
        const xhr = new XMLHttpRequest();
        const formData = new FormData();
        for(let i in pInput.dataset)
        {
            if(pInput.dataset.hasOwnProperty(i))
                formData.append(i, pInput.dataset[i]);
        }
        formData.append(pInput.dataset.input_name, pFile);
        xhr.upload.addEventListener('progress', function(e)
        {
            updateProgress(pInput, (e.loaded/ e.total)*100);
        });

        xhr.open('POST', 'statique/upload-async/');

        xhr.onreadystatechange=function()
        {
            if(xhr.readyState===4)
            {
                switch(xhr.status)
                {
                    case 304:
                    case 200:
                        const ct = xhr.getResponseHeader("Content-Type");
                        if(ct.indexOf("json")>-1){
                            xhr.responseJSON = JSON.parse(xhr.responseText);
                        }
                        if(xhr.responseJSON.error && xhr.responseJSON.error !== "")
                        {
                            resetUploadInput(pInput);
                            alert(xhr.responseJSON.error);
                            return;
                        }
                        updateProgress(pInput, 100).onComplete(function(){
                            uploadCompleted(pInput, xhr.responseJSON);
                        });
                        break;
                    case 403:
                    case 404:
                    case 500:
                        resetUploadInput(pInput);
                        alert("Upload Impossible");
                        console.error("Upload impossible");
                        break;
                }
            }
        };
        xhr.send(formData);
    }

    function updateProgress(pInput, pValue)
    {
        let value = pValue||"";
        if(value !== "")
        {
            value = Math.round(Number(value));
            M4Tween.killTweensOf(pInput.dataset);
            return M4Tween.to(pInput.dataset, 1, {progress:value, useStyle:false})
                        .onUpdate(
                        function()
                        {
                            pInput.parentNode.querySelector('div.status_bar .background').style.width = Math.round(Number(pInput.dataset.progress))+"%";
                            pInput.parentNode.querySelector('div.status_bar .foreground').innerHTML = Math.round(Number(pInput.dataset.progress))+"%";
                        });
        }
        else
        {
            pInput.parentNode.querySelector('div.status_bar .foreground').innerHTML = value;
            return true;
        }
    }

    function resetUploadInput(pInput)
    {
        const hidden = pInput.parentNode.querySelector('input[type="hidden"]');
        if(pInput.dataset.delete_file_action)
        {
            let action = pInput.dataset.delete_file_action;
            const id = hidden.value;
            if(action.indexOf('{id}')>-1)
            {
                action = action.replace('{id}', id);
            }
            const xhr = new XMLHttpRequest();
            xhr.open('GET', action);
            xhr.send();
        }
        hidden.value = "";
        M4Tween.killTweensOf(pInput.dataset);
        pInput.dataset.progress = "0";
        pInput.parentNode.querySelector('div.status_bar .foreground').innerHTML = "";
        pInput.value = pInput.defaultValue;
        updateProgress(pInput, '');
        pInput.parentNode.className = "input upload "+states.IDLE;
    }

    function uploadCompleted(pInput, pData)
    {
        pInput.parentNode.querySelector('input[type="hidden"]').value = pData.id_upload;
        const aFile = pInput.parentNode.querySelector('a.file');
        aFile.href = pData.path_upload;
        if (/\.(png|gif|jpg|jpeg)$/i.exec(pData.path_upload))
        {
            aFile.classList.add('img');
            aFile.innerHTML = "<img src='"+aFile.href+"' alt=''>";
        }
        else
        {
            aFile.classList.remove('img');
            aFile.innerHTML = dict.seeFile+"<span></span>";
        }
        pInput.value = pInput.defaultValue;
        pInput.parentNode.className = "input upload "+states.HAS_FILE;
    }

    window.addEventListener('DOMContentLoaded', init, false);

    return {
        init:init,
        resetUploadInput:resetUploadInput,
        uploadCompleted:uploadCompleted
    };
})();