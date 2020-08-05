/**
 * Created by furkan on 01.04.2015.
 */


var userPurchaseModule = angular.module('userPurchaseModule', []);

userPurchaseModule.service('userPurchase',['$http',function($http)
{
    $http.defaults.headers.post["Content-Type"] = "application/json";

    var purchases= [];

    return{
        getPurchases : function() {
            return purchases;
        },
        setPurchases : function(values) {
            purchases = values;
        },
        initPurchases : function(access_token, callbackFunc) {
            var data = {
            access_token : access_token
            };
            $http.post(webServer + '/user-sale-list',data).success(function (response) {
                purchases = response;
                callbackFunc(true);
            }).error(function(data, status) {
                callbackFunc(false);
            });
        },
        submitOrderRequest : function(paymentInfo,flowerImage,flowerName, callbackFunc)
        {
            var submitUrl;
            if(paymentInfo.access_token){
                submitUrl = webServer +'/user-submit-order-request';
            }
            else{
                submitUrl = webServer +'/submit-order-request';
            }

            $http.post(submitUrl, paymentInfo).success( function (data)
            {
                callbackFunc(true,data);

            }).error( function(data, status, headers, config )
            {
                callbackFunc(false, data.description);
            });
        }
    }
}]);