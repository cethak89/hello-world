'use strict';

/**
 * Created by furkan on 25.03.2015.
 */

var newsLetterServiceModule = angular.module('newsSubscriptionService', []);

newsLetterServiceModule.service('newsLetter',['$http','otherExceptions',function($http,otherExceptions){
    return {
        addNewUserToList : function(userMail, callbackFunction){
            var data = {
                mail : userMail
            };

            $http.post(webServer + '/newsletter',data).success(function (response)
            {
                callbackFunction(true);
            }).error( function(data)
            {
                callbackFunction(false, data.description);
                otherExceptions.sendException("newsLetter-subscription",  "Subscribe Olurken Serverdan Hata Döndü");
            });
        }
    };
}]);