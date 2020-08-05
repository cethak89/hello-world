/**
 * Created by furkan on 02.04.2015.
 */

var userBillingModule = angular.module('userBillingModule', []);

userBillingModule.service('userBillingService',['$http',function($http){
    var billingInfo = {};

    var setBillingInfo = function(values,billingType){
        billingInfo = values;
        billingInfo.billing_type = billingType;
    };

    var insertBilling = function(billingType, access_token,billingAddress, callbackFunc){

        var newBillingInfo = {
            access_token : access_token,
            billing_address : billingAddress
        };

        var postUrl;

        if(billingType === "1"){
            postUrl = webServer +'/user-insert-personal-billing';
            newBillingInfo.small_city = arguments[4];
            newBillingInfo.city = arguments[5];
            newBillingInfo.tc = arguments[6];
        }
        else{
            postUrl = webServer +'/user-insert-company-billing';
            newBillingInfo.company = arguments[4];
            newBillingInfo.tax_office = arguments[5];
            newBillingInfo.tax_no = arguments[6];
        }

        $http.post(postUrl,newBillingInfo).success(function (data) {
            newBillingInfo.id = data.id;
            setBillingInfo(newBillingInfo, billingType);
            callbackFunc(true);
        }).error(function(data){
            callbackFunc(false, data.description);
        });
    };

    var updateBilling = function(billingType, access_token, billingAddress ,callbackFunc) {
        var updateBillingInfo = {
            id: billingInfo.id,
            access_token : access_token,
            billing_address : billingAddress
        };

        var postUrl;

        if(billingType === "1"){
            postUrl = webServer + '/user-update-personal-billing';
            updateBillingInfo.small_city = arguments[4];
            updateBillingInfo.city = arguments[5];
            updateBillingInfo.tc = arguments[6];
        }
        else{
            postUrl = webServer + '/user-update-company-billing';
            updateBillingInfo.company = arguments[4];
            updateBillingInfo.tax_office = arguments[5];
            updateBillingInfo.tax_no = arguments[6];
        }

        $http.post(postUrl,updateBillingInfo).success(function (data) {
            setBillingInfo(updateBillingInfo,billingType);
            callbackFunc(true);
        }).error(function(data){
            callbackFunc(false, data.description);
        });
    };

    return{
        getBillingInfo : function(){
            return billingInfo;
        },
        setBillingInfo : function(values){
            setBillingInfo(values);
        },
        initBillingInfo : function(access_token, callbackFunc){
            var data = {
                access_token : access_token
            };
            $http.post( webServer+'/user-get-billing',data).success(function (response) {
                billingInfo = response[0];
                callbackFunc(true);
            }).error(function(data, status) {
                callbackFunc(false, data.description);
            });
        },
        updateBillingInfo : function(billingType, access_token, userBillingAddress, callbackFunc){

            if(billingInfo === undefined){
                if(billingType === "1")
                    insertBilling(billingType, access_token, userBillingAddress, callbackFunc,arguments[4], arguments[5],arguments[6]);
                else
                    insertBilling(billingType, access_token, userBillingAddress,callbackFunc, arguments[4], arguments[5], arguments[6]);
            }
            else{
                if(billingType === "1")
                    updateBilling(billingType, access_token, userBillingAddress, callbackFunc,arguments[4], arguments[5],arguments[6]);
                else
                    updateBilling(billingType, access_token, userBillingAddress,callbackFunc, arguments[4], arguments[5], arguments[6]);
            }
        }
    }
}]);