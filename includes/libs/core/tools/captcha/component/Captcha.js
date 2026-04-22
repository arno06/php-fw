NodeList.prototype.forEach = NodeList.prototype.forEach||Array.prototype.forEach;
class WebCCaptcha extends HTMLElement
{
    static LS_KEY = "webc-captcha";

    static #TEMPLATE = `
<style>
    :host{
        font-family: Arial, Helvetica, sans-serif;
        font-size:14px;
        --svg-loader: url('data:image/svg+xml,<svg fill="hsl(201 14% 44%)" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z"><animateTransform attributeName="transform" type="rotate" dur="0.75s" values="0 12 12;360 12 12" repeatCount="indefinite"/></path></svg>');
        --svg-checkmark:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" enable-background="new 0 0 64 64"><path d="M32,2C15.431,2,2,15.432,2,32c0,16.568,13.432,30,30,30c16.568,0,30-13.432,30-30C62,15.432,48.568,2,32,2z M25.025,50l-0.02-0.02L24.988,50L11,35.6l7.029-7.164l6.977,7.184l21-21.619L53,21.199L25.025,50z" fill="%2343a047"/></svg>');
        --svg-error:url('data:image/svg+xml,<%3Fxml version="1.0" encoding="UTF-8" standalone="no"%3F><svg xmlns:osb="http://www.openswatchbook.org/uri/2009/osb" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns%23" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns%23" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" version="1.1"><defs id="defs7"><linearGradient id="linearGradient828" osb:paint="solid"><stop style="stop-color:%23ff0000;stop-opacity:1;" offset="0" id="stop826" /></linearGradient><linearGradient id="0" gradientUnits="userSpaceOnUse" y1="47.37" x2="0" y2="-1.429"><stop stop-color="%23c52828" id="stop2" /><stop offset="1" stop-color="%23ff5454" id="stop4" /></linearGradient></defs><g transform="matrix(.99999 0 0 .99999-58.37.882)" enable-background="new" id="g13" style="fill-opacity:1"><circle cx="82.37" cy="23.12" r="24" fill="url(%230)" id="circle9" style="fill-opacity:1;fill:%23dd3333" /><path d="m87.77 23.725l5.939-5.939c.377-.372.566-.835.566-1.373 0-.54-.189-.997-.566-1.374l-2.747-2.747c-.377-.372-.835-.564-1.373-.564-.539 0-.997.186-1.374.564l-5.939 5.939-5.939-5.939c-.377-.372-.835-.564-1.374-.564-.539 0-.997.186-1.374.564l-2.748 2.747c-.377.378-.566.835-.566 1.374 0 .54.188.997.566 1.373l5.939 5.939-5.939 5.94c-.377.372-.566.835-.566 1.373 0 .54.188.997.566 1.373l2.748 2.747c.377.378.835.564 1.374.564.539 0 .997-.186 1.374-.564l5.939-5.939 5.94 5.939c.377.378.835.564 1.374.564.539 0 .997-.186 1.373-.564l2.747-2.747c.377-.372.566-.835.566-1.373 0-.54-.188-.997-.566-1.373l-5.939-5.94" fill="%23fff" fill-opacity=".842" id="path11" style="fill-opacity:1;fill:%23ffffff" /></g></svg>');
    }
    .webc-captcha-container{position:relative;border:solid 1px #ddd;border-radius:4px;max-width:400px;background:#fafafa;padding:1em;}
    .webc-captcha-container.webc-captcha-block{border:solid 1px #c52828;outline:solid 1px #c52828;}
    .webc-captcha-container.webc-captcha-valid{border:solid 1px #43a047;}
    .webc-captcha-loader-overlay,.webc-captcha-loader{background: no-repeat var(--svg-loader);padding-left:30px;font-weight: bold;color:hsl(201 14% 44%);}
    .webc-captcha-loader-overlay{padding:1em 1em 1em calc(1em + 30px);position:absolute;left:0;top:0;width:100%;height:100%;display:flex;align-items: center;box-sizing: border-box;background-size:18px;background-position:left 1em center;backdrop-filter: blur(5px);}
    .webc-captcha-verified{background: no-repeat var(--svg-checkmark);color:#43a047;font-weight: bold;padding-left:30px;}
    .webc-captcha-error{background: left center/18px no-repeat var(--svg-error);color:#c52828;font-weight: bold;padding-left:30px;}
    .webc-captcha-options{display:flex;padding:0.5em;gap:0.5em;}
    .webc-captcha-options>img{cursor:pointer;padding:2px;}
    .webc-captcha-options>img:hover{outline:solid 1px #b4f4ff;}
</style>
<div class="webc-captcha-container">
    <div class="webc-captcha-loader" data-label="loading"></div>
</div>
`;


    static DICTIONARY = {
        'fr-FR':{
            loading:'Vérification en cours...',
            status_verified:'Vérification terminée.',
            too_many_attempts:'Vous avez soumis trop de mauvaises réponses.<br/>Veuillez attendre %x',
            wrong_answer:'La solution soumise est incorrecte'
        },
        'en-UK':{
            loading:'Verification in progress...',
            status_verified:'Verification complete.',
            too_many_attempts:'You have submitted too many incorrect answers.<br/>Please wait %x',
            wrong_answer:'The submitted solution is incorrect'
        },
        'es-SP':{
            loading:'Verificación en curso...',
            status_verified:'Verificación completada',
            too_many_attempts:'Has introducido demasiadas respuestas incorrectas.<br/>Por favor, espera %x',
            wrong_answer:'La solución enviada es incorrecta'
        }
    };

    static EVENT_THRESHOLD = 70;//ms

    constructor(){
        super();
        this.verified = false;
        this.token = null;
        this.defaultLanguage = 'fr-FR';
        this.backendUrl = 'statique/webc-captcha/';
        this.microtime = null;
        this.optionTarget = null;
    }

    connectedCallback() {
        this.shadow = this.attachShadow({mode: 'closed'});
        this.#checkState();
        this.inputElement = document.createElement("input");
        this.inputElement.setAttribute("type", "hidden");
        if(this.getAttribute("name")){
            this.inputElement.setAttribute("name", this.getAttribute("name"));
            this.removeAttribute("name");
        }
        this.insertAdjacentElement('afterend', this.inputElement);
        this.inputElement.form.addEventListener('submit', this.#formSubmitHandler.bind(this));
        this.shadow.innerHTML = `
           ${WebCCaptcha.#TEMPLATE}
        `;
        this.#i18n();
        setTimeout(this.#loadCaptcha.bind(this), 500);
    }

    #loadCaptcha(){
        this.#makeRequest('GET', null)
        .then((pJson)=>{
            let container = this.shadow.querySelector('.webc-captcha-container');

            if(pJson.question && pJson.response){
                container.innerHTML = `
            <div class='webc-captcha-question'>${pJson.question}</div>
            ${pJson.response}
`;
                container.querySelectorAll('.webc-captcha-options img').forEach((pItem, pIndex)=>{
                    pItem.setAttribute("data-value", pIndex);
                    pItem.setAttribute("tabindex", pIndex+1);
                    pItem.addEventListener('keydown', this.#itemKeyDownHandler.bind(this));
                    pItem.addEventListener('focus', this.#itemFocusHandler.bind(this));
                    pItem.addEventListener('mouseover', this.#itemMouseOverHandler.bind(this));
                    pItem.addEventListener('click', this.#itemClickHandler.bind(this));
                });
            }
            this.#genericResponse(pJson);
        });
    }

    #genericResponse(pJson){
        let container = this.shadow.querySelector('.webc-captcha-container');
        if(pJson.verified){
            this.verified = true;
            container.innerHTML = `
            <div class='webc-captcha-verified' data-label="status_verified"></div>
`;
            this.#enableForm();
        }
        if(pJson.too_many_attempts){
            let waiting = pJson.waiting_time;
            let units = "sec";
            if(waiting>=60){
                let sec = waiting%60;
                waiting = Math.floor(waiting / 60);
                units = "min";
                units += sec+"sec";
            }
            if(waiting >= 60){
                let min = waiting%60;
                waiting = Math.floor(waiting / 60);
                units = "h";
                units += min+"min";
            }
            let message = this.#getLabel("too_many_attempts").replace('%x', waiting+units);
            container.innerHTML = `
            <div class='webc-captcha-error'>${message}</div>
`;
            this.#disableForm();
        }
        if(pJson.wrong_answer){
            container.insertAdjacentHTML('beforeend', '<div class="webc-captcha-error" data-label="wrong_answer"></div>');
            this.#disableForm();
        }
        this.#i18n();
        this.token = pJson.token;
        this.#saveState();
    }

    #itemFocusHandler(e){
        this.optionTarget = e.currentTarget;
        this.microtime = Date.now();
    }

    #itemKeyDownHandler(e){
        if([13,32].indexOf(e.keyCode) === -1){
            return;
        }
        if(this.optionTarget !== e.currentTarget){
            return;
        }
        if((Date.now()) - this.microtime < WebCCaptcha.EVENT_THRESHOLD){
            return;
        }
        this.#submitSolution(e.currentTarget.getAttribute("data-value"));
    }

    #itemMouseOverHandler(e){
        this.optionTarget = e.currentTarget;
        this.microtime = Date.now();
    }

    #itemClickHandler(e){
        if(this.optionTarget !== e.currentTarget){
            return;
        }
        if((Date.now()) - this.microtime < WebCCaptcha.EVENT_THRESHOLD){
            return;
        }
        this.#submitSolution(e.currentTarget.getAttribute("data-value"));
    }

    #submitSolution(pValue){
        let container = this.shadow.querySelector('.webc-captcha-container');
        container.querySelector('.webc-captcha-error')?.remove();
        container.insertAdjacentHTML('afterbegin', '<div class="webc-captcha-loader-overlay" data-label="loading"></div>');
        this.#i18n();
        this.#makeRequest('POST', {value:pValue}).then((pJson)=>{
            container.querySelector('.webc-captcha-loader-overlay').remove();
            this.#genericResponse(pJson);
        });
    }

    #formSubmitHandler(e){
        if(!this.verified){
            e.preventDefault();
            this.#disableForm();
        }
    }

    #makeRequest(pMethod, pParams = null){
        let reqInit = {
            method:pMethod||'GET',
            headers:{
                'x-http-from':'webc-catpcha',
                'x-http-with':'fetch',
                'x-token':this.token !== null ? this.token:""
            }
        };
        if(pParams && pMethod !== "GET"){
            let data = new URLSearchParams();
            for(let i in pParams){
                if(!pParams.hasOwnProperty(i)){
                    continue;
                }
                data.append(i, pParams[i]);
            }
            reqInit.body = data;
        }
        return fetch(this.backendUrl, reqInit).then((pResponse)=>pResponse.json());
    }

    #i18n(){
        this.shadow.querySelectorAll('.webc-captcha-container *[data-label]').forEach((pElement)=>{
            pElement.innerHTML = this.#getLabel(pElement.getAttribute("data-label"));
        });
    }

    #getLabel(pId){
        let val = WebCCaptcha.DICTIONARY[this.defaultLanguage]||WebCCaptcha.DICTIONARY['fr-FR'];
        let parts = pId.split(".");
        for(let i = 0, max = parts.length; i<max; i++){
            let id = parts[i];
            if(!val[id]){
                return "Undefined";
            }
            val = val[id];
        }
        return val;
    }


    async #checkState(){
        let storage = localStorage.getItem(WebCCaptcha.LS_KEY);
        let data = JSON.parse(storage?atob(storage):'{}');
        if(!data || !data.token){
            return false;
        }
        this.token = data.token;
    }

    #saveState(){
        let data = {token: this.token};
        this.inputElement.setAttribute("value", this.token);
        this.inputElement.value = this.token;
        localStorage.setItem(WebCCaptcha.LS_KEY, btoa(JSON.stringify(data)));
    }

    #disableForm(){
        this.shadow.querySelector('.webc-captcha-container').classList.add("webc-captcha-block");
        this.inputElement.form.querySelectorAll('input[type="button"],input[type="submit"],button').forEach((pElement)=>{
            pElement.setAttribute("disabled", "disabled");
        });
    }

    #enableForm(){
        this.shadow.querySelector('.webc-captcha-container').classList.remove("webc-captcha-block");
        this.shadow.querySelector('.webc-captcha-container').classList.add("webc-captcha-valid");
        this.inputElement.form.querySelectorAll('input[type="button"],input[type="submit"],button').forEach((pElement)=>{
            pElement.removeAttribute("disabled");
        });
    }


}

window.customElements.define('webc-captcha', WebCCaptcha);