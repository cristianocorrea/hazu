var HazuService = angular.module('Hazu',[]);


//TODO: implementar suporte a interceptors
HazuService.provider('$hazuService', {
    
    //'_interceptors': {},
    '_httpConf': {'headers': {'Content-Type': 'application/json'}},
    '_serverUrl': '',
    
    //setInterceptor: function(on, call) {
    //    interceptors[on] = call;
    //},
 
    setHeader: function(header, value) {
        this._httpConf.headers[header] = value;
    },
    
    setServerUrl: function(url) {
        this._serverUrl = url;
    },
    
    $get: ['$q','$http',function($q,$http) {
        var _this = this;
        
        return {
            call: function(url, params) {
                var deferred = $q.defer();
                
                var onSucess = function(response) {
                    deferred.resolve(response.data);
                };
                
                var onError = function(response) {
                    deferred.reject(response.data);
                };
                
                var finalUrl = _this._serverUrl + url;
                
                $http.post(finalUrl, params, _this._httpConf).then(onSucess, onError);
                  
                return deferred.promise;
            }
        };
    }]
});