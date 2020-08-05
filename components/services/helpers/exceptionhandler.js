'use strict';

/**
 * @ngdoc service
 * @name bloomNFresh.exceptionHandler
 * @description
 * # exceptionHandler
 * Service in the bloomNFresh.
 */
var errors = [
    {error_id:400, error_name:'ERROR'},
    {error_id:401, error_name:'WRONG_MAIL_OR_PASSWORD'},
    {error_id:402, error_name:'ALREADY_REGISTERED_MAIL'},
    {error_id:403, error_name:'ALREADY_REGISTERED_USER'},
    {error_id:404, error_name:'WRONG_MAIL'},
    {error_id:405, error_name:'TOO_MANY_REQUEST'},
    {error_id:406, error_name:'WRONG_COUPON'},
    {error_id:407, error_name:'WRONG_PASSWORD'},
    {error_id:408, error_name:'PASSED_DATE'},
    {error_id:409, error_name:'INVALID_COUPON'},
    {error_id:410, error_name:'ALREADY_REGISTERED_MAIL_2'},
    {error_id:411, error_name:'USER_NOT_FOUND'},
    {error_id:412, error_name:'TOO_MANY_REQUEST_2'},
    {error_id:413, error_name:'MAIL_NOT_FOUND'},
    {error_id:414, error_name:'FB_LOGIN_ERROR'},
    {error_id:415, error_name:'USER_NOT_FOUND_2'},
    {error_id:416, error_name:'EXPIRED_LINK'},
    {error_id:417, error_name:'WRONG_LINK'},
    {error_id:418, error_name:'FAILED_CHECKOUT'},
    {error_id:419, error_name:'FLOWER_COUPON_ERROR'},
    {error_id:420, error_name:'MISSING_INFO'},
    {error_id:421, error_name:'REGISTRATION_CONTRACT_NOT_ACCEPTED'},
    {error_id:422, error_name:'PASSED_DATE_SALE'},
    {error_id:423, error_name:'ALREADY_GIVEN_EMAIL'},
    {error_id:424, error_name:'FLOWER_IS_AVAILABLE'},
    {error_id:430, error_name:'FLOWER_NOT_OK'}
];

angular.module('app')
    .factory('$exceptionHandler', function ($window,$log,deviceDetector) {
        return function (exception, cause) {
            $log.error.apply($log, arguments);
            try {
                var errorMessage = exception.toString();
               // var stackTrace = traceService.print({ e : exception });
                $.ajax({
                    type : "POST",
                    url : webServer + "/logClient",
                    contentType : "application/json",
                    data : angular.toJson(
                        {
                            method_name : $window.location.href,
                            error_message : errorMessage,
                            error_code : deviceDetector.getBrowserInfo().browser + "/" + deviceDetector.getBrowserInfo().version + "/" + deviceDetector.isMobile()//,
                            //method_name: stackTrace
                        })
                });
            }
            catch (loggingError){
                $log.warn("Error server-side logging failed");
                $log.log(loggingError);
            }
        };
    })
    .service('otherExceptions', function ($http,$window,deviceDetector) {
        return{
            sendException : function (exceptionLocation,errorMessage) {
                var exceptionInfo = {
                    "method_name" : exceptionLocation,
                    "error_code" : deviceDetector.getBrowserInfo().browser + "/" + deviceDetector.getBrowserInfo().version + "/" + deviceDetector.isMobile(),
                    "error_message" : errorMessage,
                    "url" : $window.location.href
                };

                $http.post(webServer + '/logClient',exceptionInfo);
            }
        };
    })
    .factory("traceService", function () {
        return ({ print : printStackTrace });
    })
    .service('errorMessages', function(translateHelper){

        this.getErrorMessage = function(errorCode,callback){
            var errorName;

            for(var i in errors){
                if(errors[i].error_id == errorCode){
                    errorName = errors[i].error_name;
                    break;
                }
            }

            translateHelper.getText(errorName, function(errorMessage){
                callback(errorMessage);
            });
        };

        this.getErrorMessageFromName = function(ErrorName, callback){
            translateHelper.getText(ErrorName, function(errorMessage){
                callback(errorMessage);
            });
        };


    });