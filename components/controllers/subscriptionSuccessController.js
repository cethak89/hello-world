'use strict';

var subsModule = angular.module("subscriptionSuccess",
    [
        'menuModule',
        'footerModule',
        'PageTagsFactoryModule',
        'userAccountModule'
    ]);

subsModule.controller("subscriptionSuccessController", function ($timeout,$scope, $http,$sce, $cookies, $stateParams, $state,momentHelper, facebookhelper, userAccount, adwordsHelper, flowerFactory, analyticsHelper, PageTagsFactory) {
    $scope.canEditBilling = true;

    if ($stateParams.orderId === undefined) {
        $state.go('landing');
    } else {
        $http.get(webServer + '/subs-success-data/' + $stateParams.orderId)
            .success(function (response) {
                var momentObjStart = momentHelper.getTime('YYYY-MM-DD HH:mm:ss',moment(response.data.first_delivery_date));
                $scope.receiveDate = momentHelper.getTime('DD MMMM YYYY, dddd',momentObjStart);

                var tempPriceSuccess = parseFloat(replaceString(response.data.flower_price, ",", ".")).toFixed(2);

                tempPriceSuccess = (tempPriceSuccess / 100 * 118).toFixed(2);

                tempPriceSuccess = replaceString(tempPriceSuccess, ".", ",");

                var tempPriceCup = parseFloat(replaceString(response.data.cup_price, ",", ".")).toFixed(2);

                tempPriceCup = (tempPriceCup / 100 * 118).toFixed(2);

                tempPriceCup = replaceString(tempPriceCup, ".", ",");

                $scope.purchaseInfo = {
                    price: tempPriceSuccess,
                    name: response.data.contact_name,
                    flower_name: response.data.flower_name,
                    address : response.data.contact_address,
                    senderNote : response.data.note,
                    flowerImage : response.data.flowerImage,
                    cup_status : response.data.cup_status,
                    cup_price : tempPriceCup,
                    freqName : response.data.freqName
                };

                console.log(response);

                //var tempNameList = $scope.purchaseInfo.name.split(" ");
                //var tempCompleteName = "";
                //for( var x = 0; x < tempNameList.length; x++ ){
                //    tempCompleteName += tempNameList[x].charAt(0).toUpperCase() + tempNameList[x].slice(1) + " ";
                //}
//
                //$scope.purchaseInfo.name = tempCompleteName;


                $timeout(function () {
                    var tempMail  = "";
                    if (userAccount.checkUserLoggedin()){
                        tempMail = userAccount.getUserMail();
                        tempMail.toLowerCase();
                    }
                }, 1000);
            })
            .error(function (response) {
                $state.go('landing');
            });
    }

    $scope.changeEditBilling = function () {
        $scope.canEditBilling = !$scope.canEditBilling;
    };
});

subsModule.controller('StudioPurchaseBillingCtrl', function ($scope, $http, $timeout,$stateParams , $document,errorMessages, userAccount,translateHelper, otherExceptions) {
    $scope.working = false;
    $scope.errorMessage = "";
    $scope.invoice = {};
    $scope.processSuccess = false;

    $document.ready(function () {
        $('.icheckField input').iCheck({
            checkboxClass : 'icheckbox_square-red',
            radioClass : 'iradio_flat-red'
        });
    });

    $scope.billingSection = 1;
    $('input.purchasePersonalBilling').iCheck('check');

    userAccount.getUser(function(userData){
        $scope.user = userData;

        if(userData){
            $scope.invoice.name = $scope.user.name;
            var billingInfo = $scope.user.billingInfo.getBillingInfo();

            if (billingInfo !== undefined) {

                if (billingInfo.billing_type === "1") {
                    $('input.purchasePersonalBilling').iCheck('check');
                    $scope.billingSection = 1;
                    $scope.invoice.city = billingInfo.city;
                    $scope.invoice.small_city = billingInfo.small_city;
                    if(billingInfo.tc)
                        $scope.invoice.tc = billingInfo.tc;
                    $scope.invoice.personal_address = billingInfo.personal_address;
                    $scope.invoice.billing_address = billingInfo.billing_address;
                    $scope.invoice.company = billingInfo.company;
                    $scope.invoice.tax_office = billingInfo.tax_office;
                    $scope.invoice.tax_no = billingInfo.tax_no;
                } else {
                    $('input.purchaseCorporateBilling').iCheck('check');
                    $scope.billingSection = 2;
                    $scope.invoice.billing_address = billingInfo.billing_address;
                    $scope.invoice.company = billingInfo.company;
                    $scope.invoice.tax_office = billingInfo.tax_office;
                    $scope.invoice.tax_no = billingInfo.tax_no;
                    $scope.invoice.city = billingInfo.city;
                    $scope.invoice.small_city = billingInfo.small_city;
                    if(billingInfo.tc)
                        $scope.invoice.tc = billingInfo.tc;
                    $scope.invoice.personal_address = billingInfo.personal_address;
                }
            }
        }
    },true);

    $('input.purchasePersonalBilling')
        .on('ifChecked', function () {
            $timeout(function () {
                $scope.billingSection = 1;
                $scope.isChecked = false;
            });
        })
        .on('ifUnchecked', function () {
            $timeout(function () {
                $scope.billingSection = 2;
                $scope.isChecked = false;
            });
        });

    $('input#isSentBill')
        .on('ifChecked', function () {
            $timeout(function () {
                $scope.invoice.billing_send = false;
            });
        })
        .on('ifUnchecked', function () {
            $timeout(function () {
                $scope.invoice.billing_send = true;
            });
        });

    $scope.radioSelected = function (radioName) {
        switch (radioName) {
            case 'personal':
            {
                $timeout(function () {
                    $scope.billingSection = 1;
                    $scope.isChecked = false;
                    $('input.purchasePersonalBilling').iCheck('check');
                });
                break;
            }
            case 'corporate':
            {
                $timeout(function () {
                    $scope.billingSection = 2;
                    $scope.isChecked = false;
                    $('input.purchaseCorporateBilling').iCheck('check');
                });
                break;
            }
        }
    };

    $scope.sendBillingInfo = function () {
        if ($scope.billingSection === 1) {
            if ($(invoiceForm.user_name).hasClass('ng-valid') && $(invoiceForm.city).hasClass('ng-valid') && $(invoiceForm.tc_no).hasClass('ng-valid') &&
                $(invoiceForm.small_city).hasClass('ng-valid') && $(invoiceForm.personal_address).hasClass('ng-valid')) {
                $scope.invoice.billing_address = $scope.invoice.personal_address;
                sendBilling();
            } else {
                errorMessages.getErrorMessage("MISSING_INFO", function(errorMessage){
                    $scope.isChecked = true;
                    otherExceptions.sendException("PersonalInfo-purchaseSuccess", "Eksik Bilgi");
                    $scope.errorMessage = errorMessage;
                });
            }
        }
        else if ($scope.billingSection === 2) {
            if ($(invoiceForm.company_address).hasClass('ng-valid') && $(invoiceForm.company).hasClass('ng-valid') &&
                $(invoiceForm.tax_office).hasClass('ng-valid') && $(invoiceForm.tax_no).hasClass('ng-valid')) {
                sendBilling();
            } else {
                errorMessages.getErrorMessage("MISSING_INFO", function(errorMessage){
                    $scope.isChecked = true;
                    otherExceptions.sendException("CompanyBilling-purchaseSuccess", "Eksik Bilgi");
                    $scope.errorMessage = errorMessage;
                });
            }
        }
    };

    function sendBilling() {
        var postUrl = "/addStudioBillingInfo";
        $scope.invoice.sales_id = $stateParams.orderId;
        $scope.invoice.billing_type = $scope.billingSection;

        if ($scope.isUserLogin()) {
            postUrl = "/addStudioBillingInfo";
            $scope.invoice.access_token = $scope.user.access_token;
        }

        $scope.working = true;
        $http.post(webServer + postUrl, $scope.invoice)
            .success(function (response) {
                $scope.working = false;
                $scope.processSuccess = true;
                $scope.isChecked = false;
                $scope.errorMessage = "";

                $timeout(function () {
                    $scope.canEditBilling = false;
                },3000);
            }).error(function (response) {
                translateHelper.getText(response.description, function(errorText){
                    $scope.errorMessage = errorText;
                    $scope.working = false;
                    $scope.isChecked = true;
                    otherExceptions.sendException("satış özeti","fatura gönderirken hata oldu");
                });
            });
    }
});
