'use strict';

/**
 * @ngdoc service
 * @name app.productSubscriptionHelper
 * @description
 * # productSubscriptionHelper
 * Service in the bloomNFresh.
 */
angular.module('app')
  .service('productSubscriptionHelper', function ($http) {
        this.subscribeMail = function(flowerId, mail, tempDistrictId, callback){
            var data =
            {
                "product_id" : flowerId,
                "mail" : mail,
                "city_id" : tempDistrictId
            };
            $http.post(webServer + '/add-product-reminder-mail-with-city', data)
                .success(function () {
                    callback(true);
                }).error(function (result) {
                    callback(false, result.description);
                });
        }
  });
