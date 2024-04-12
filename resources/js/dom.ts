function ready(callback: ()=>void):void  {
    if (document.readyState != "loading") callback();
    else document.addEventListener("DOMContentLoaded", callback);
}

export {ready};