var Autocomplete = (function(){

    var pool = [];

    function handlePool()
    {
        for(var i = 0, max = pool.length; i<max;i++)
        {
            setup(pool[i]);
        }
        pool = [];
    }

    window.addEventListener('load', handlePool, false);

    function setup(pSelector)
    {
        if(!document.querySelector(pSelector))
        {
            pool.push(pSelector);
            return false;
        }

        document.querySelector(pSelector).addEventListener('keyup', keyUpHandler, false);
    }

    function keyUpHandler(e)
    {
        console.log("@todo implement everything");
    }


    return {
        applyTo:setup
    }
})();