var storageService = function storageService() {};

storageService.local = "local";
storageService.session = "session";

storageService.getValue = function (name, localOrSession) {
    if (window.storageService.check()) {

        if (localOrSession === "local")
            var value = localStorage.getItem(name);
        else if (localOrSession === "session")
            var value = sessionStorage.getItem(name);

        if (!value)
            return null;

        return JSON.parse(value);
    } else {
        //Something else for non-HTML5-compliant browsers?
    }

};

storageService.setValue = function (name, value, localOrSession) {
    if (window.storageService.check()) {
        if (localOrSession === "local")
            localStorage.setItem(name, JSON.stringify(value));
        else if (localOrSession === "session")
            sessionStorage.setItem(name, JSON.stringify(value));
    }
};

storageService.check = function() {
    return !(typeof(Storage) === 'underfined');
};

if (!window.storageService) {
    window.storageService = storageService;
}