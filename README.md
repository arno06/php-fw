# namespaces
namespace | contexte | description
--------------|------------|--------------
core\\ *{subPackage}* \ |Global |Classes & interfaces du package core
lib\\ *{package}* \\ *{subPackage}* |Global |Classes & interfaces des packages secondaire
app\\ *{appName}* \\models |Application |Modèles de l'application *appName*
app\\ *{appName}* \\controllers\\ front |Application|Controllers de front de l'application *appName*
app\\ *{appName}* \\controllers\\ back |Application |Controllers de back de l'application *appName*
app\\ *{appName}* \\src\\ *{subPackage}* |Application |Classes & interfaces de l'application *{appName}*

# todo
    v2
        Form:
            getInput
            refonte des helpers de composants
            Components
                Select + Select Multiple
                RTE
                ColorPicker
                Autocomplete
        Core/Conf
            Externalisation de la config des modules

        Modules
            back
                Mot de passe oublié

# form
    INPUT[text|password|submit|...]
        {
            "label":"Input",
            "tag":"input",
            "require":true|false,
            "attributes":
            {
                "type":"text"|"password"|"submit"...,
                "value":"Default Value",
                "class":...
            }
        }
    CAPTCHA
        {
            "label":"Captcha",
            "tag":"captcha",
            "require":true|false,
            "backgroundColor":"#ffffff",
            "fontSizeMax":13,
            "fontSizeMin":13,
            "width":100,
            "height":30,
            "rotation":15,
            "fontColors":["#444444","#ff0000","#000000"],
            "transparent":true
        }
    DATEPICKER
        {
            "label":"Datepicker",
            "tag":"datepicker"
        }
    UPLOAD  
        {
            "label":"Fichier",
            "tag":"upload"
            "fileType":"jpg|png|...",
            "fileName":"someName{id}",
            "resize":[200, 200]
        }
    RADIOGROUP
        {
            "label":"Radiogroup",
            "tag":"radiogroup",
            "display":"block",
            "height":"200px",
            "width":"400px",
            "fromModel":
            {
                "model":"ModelName",
                "method":"all",
                "name":"field_name",
                "value":"field_name_id"
            }
        }
    CHECKBOXGROUP
        `{
            "label":"Checkboxgroup",
            "tag":"checkboxgroup",
            "height":"200px",
            "width":"400px",
            "fromModel":
            {
                "model":"ModelName",
                "method":"all",
                "name":"field_name",
                "value":"field_name_id"
            }
        }`