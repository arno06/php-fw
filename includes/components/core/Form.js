function AutoFillPlugin(pTarget)
{
	pTarget.addEventListener("focus", this._focusHandler);
	pTarget.addEventListener("blur", this._blurHandler);
	this._blurHandler({target:pTarget});
}
AutoFillPlugin.className = "autoFillBlur";
AutoFillPlugin.applyTo = function(pTarget, pValue)
{
	pTarget.setAttribute("title", pValue);
	return new AutoFillPlugin(pTarget);
};
AutoFillPlugin.prototype =
{
	_focusHandler:function(e)
	{
		if(e.target.value === e.target.getAttribute("title"))
		{
			e.target.value="";
			e.target.classList.remove(AutoFillPlugin.className);
		}
	},
	_blurHandler:function(e)
	{
		if(e.target.value === "")
		{
			e.target.value=e.target.getAttribute("title");
			e.target.classList.add(AutoFillPlugin.className);
		}
	}
};

function registerCtrlS(pHandler)
{
	if(!pHandler)
		return;
	window.addEventListener("keydown", function(e)
	{
		if(e.ctrlKey && e.keyCode===83)
		{
			document.activeElement.blur();
			Event.stop(e);
			pHandler();
		}
	});
}

function FormValidator(pName, pContext)
{
	this._inputs = [];
	this._name = pName;
	this._context = pContext;
}

FormValidator.REGEXP_TextNoHtml = /^([.^><]*)$/;
FormValidator.REGEXP_Text = /^(.*)$/;
FormValidator.REGEXP_Numeric = /^([0-9]*)$/;
FormValidator.SELECTOR_COMPONENT = "div.component";

FormValidator.prototype =
{
	_inputs:{},
	_name:null,
	_context:null,
	_values:null,
	_extracted:false,

	_inputsRequire:[],
	_inputsIncorrect:[],

	setInput:function(pName, pDatas)
	{
		this._inputs[pName] = pDatas;
	},

	setInputs:function(pInputs)
	{
		for(let i in pInputs)
        {
            if(!pInputs.hasOwnProperty(i))
                continue;
            this.setInput(i, pInputs[i]);
        }
	},

	isValid:function()
	{
		this._inputsIncorrect = [];
		this._inputsRequire = [];
		this.getValues();
		let inp, valid = true;
		for(let i in this._inputs)
		{
            if(!this._inputs.hasOwnProperty(i))
                continue;
			inp = this._inputs[i];
			if(inp["regExp"] && inp["regExp"] !== "")
			{
				if(FormValidator["REGEXP_"+inp["regExp"]])
				{
					let reg = FormValidator["REGEXP_"+inp["regExp"]];
					if(!reg.test(this._values[i]))
					{
						this._inputsIncorrect.push(inp["label"]);
						this._values[i] = "";
						valid = false;
					}
				}
				else
				{
//					console.log("regExp inconnue");
				}
			}

			if(inp["require"] && inp["require"]==="1")
			{
				if(!this._values[i] || this._values[i] === "")
				{
					this._inputsRequire.push(inp["label"]);
					valid = false;
				}
			}
		}

		return valid;
	},

	getValues:function()
	{
		if(!this._extracted)
			this._extractValues();
		return this._values;
	},

	_extractValues:function()
	{
		this._extracted = true;

		let param = {}, e, n, r = new RegExp(this._name+"\\[([a-z0-9\\_\\-]+)\\](.*)", "i");
//		var elements = this._context.querySelectorAll("*");
		let elements = this._context.elements;
		for(let i = 0, max = elements.length; i<max; i++)
		{
			e = elements[i];
			if(!e.name)
				continue;
			n = r.exec(e.name);
			if(n&&n.length)
				n = n[1]+n[2];
			else
				n = e.name;
			switch(e.nodeName.toLowerCase())
			{
				case "input":
					switch(e.type.toLowerCase())
					{
						case "hidden":
							let f = document.getElementById(e.name+"___Frame");
							if(!f)
								param[n] = e.value;
							else
							{
								param[n] = FCKeditorAPI?.GetInstance(e.name)?.GetXHTML(true);
							}
						break;
						case "submit":
						case "password":
						case "text":
							param[n] = e.value;
						break;
                        case "radio":
                            if(!e.radiogroup)
                                param[n] = e.value;
                            else
                            {
                                if(!param[n])
                                    param[n] = [];
                                param[n].push(e.value);
                            }
                            break;
						case "checkbox":
							if(e.checked)
							{
								if(n.indexOf("[]")>-1)
								{
									if(!param[n])
										param[n] = [];
									param[n].push(e.value);
								}
								else
									param[n] = e.value;
							}
							else
								param[n] = false;
						break;
					}
				break;
                case "textarea":
                    param[n] = e.value;
                    break;
				case "select":
					try
					{
						param[n] = e.getElementsByTagName("option")[e.selectedIndex].value;
					}
					catch(e){param[n] = "";}
				break;
			}
		}
		this._values = param;
	},

	getError:function()
	{
		let error = "";
		error += this._getErrorFromArray(this._inputsIncorrect, "La valeur du champ {label} est incorrecte.", "Les valeurs des champs {label} sont incorrectes.");
		error += this._getErrorFromArray(this._inputsRequire, "Le champ {label} est obligatoire.", "Les champs {label} sont obligatoires.");
		return error;
	},

	_getErrorFromArray:function(pArray, pLibelle, pLibelles)
	{
		if(!pArray.length)
			return "";
		let i = 0, error = "";
		for(;i<pArray.length;i++)
		{
			if(i>0)
				error += ", ";
			error += "<b>"+pArray[i]+"</b>";
		}
		let message = (pArray.length === 1) ? pLibelle : pLibelles;
		return "<p>"+message.replace('{label}', error)+"</p>";
	}
};