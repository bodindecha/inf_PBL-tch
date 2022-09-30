const ajax = async function(url, params=null, method="POST", resultType="json") {
    let opts = {
        url: url, type: method, resultType: resultType,
    }; if (params != null) opts.data = params;
    var response = /* $.ajax(opts); */ new Promise(function(resolve) {
        /* let req = new XMLHttpRequest();
        req.open(method, url);
        req.onload = () => resolve(JSON.parse(req.response));
        req.send(); */
        opts.success = function(result) { resolve(JSON.parse(result)); };
        $.ajax(opts);
    }); // return await response;
    var dat = await response;
    if (dat.success) return (typeof dat.info !== "undefined" ? dat.info : true);
    else dat.reason.forEach(em => app.ui.notify(1, em));
    return false;
};
/* const PBL = (function(d) {
    const cv = {
        API_URL: "/t/PBL/v2/api/main-"
    };
    var sv = {
        started: false
    };
    var initialize = function() {
        if (!sv.started) {
            sv.started = true;
            // Process

        }
    };
    return {
        init: initialize
        
    };
}(document)); top.PBL = PBL; */