'use strict';

/**
 * @ngdoc directive
 * @name app.directive:rightBottomPopUp
 * @description
 * # rightBottomPopUp
 */
angular.module('landingModule')
    .directive('rightBottomPopUp', function () {
        return {
            templateUrl: "../../views/bf-utility-pages/rightBottomPopUp-v2.0.html",
            restrict: 'E',
            controller: function ($document, translateHelper, $scope, $rootScope, $timeout, userAccount, userPurchase, $http, otherExceptions) {
                $scope.popUpOpen = false;
                $scope.dailySales = [];
                var ispopUpShow = true;
                showPopUp();

                $scope.popOfType = 'login';

                if(userAccount.checkUserLoggedin()){

                    if($scope.loggedUser){

                        $http.get(webServer + '/getFBIfNewsLetter/' + $scope.loggedUser.id )
                            .success(function (result) {

                                if( result.status == 1 ){
                                    $timeout(function () {
                                        $scope.popUpOpen = true;
                                        $scope.popOfType = 'newsletter';

                                    }, 1000);
                                }
                            })
                            .error(function(){
                                otherExceptions.sendException("dailySaleInfo",  "Günlük siparişleri çekerken hata döndü!");
                            });

                        $http.get(webServer + '/getDailySalesInfo/' + $scope.loggedUser.id )
                            .success(function (result) {
                                $scope.dailySales = result;

                                if( $scope.dailySales.length > 0 ){
                                    $timeout(function () {
                                        $scope.popUpOpen = true;
                                        $scope.popOfType = 'sales';

                                    }, 1000);

                                    $timeout(function () {

                                        $('.saleDetailPopUp input').iCheck({
                                            checkboxClass : 'icheckbox_square-red',
                                            radioClass : 'iradio_flat-red'
                                        });

                                        $('input.newsletterCheckbox')
                                            .on('ifChecked', function (event) {
                                                $scope.loggedUser.sale_info = 0;

                                                $http.get(webServer + '/updateSaleDetailInfo/' + $scope.loggedUser.id + '/0' )
                                                    .success(function (result) {

                                                    });
                                            })
                                            .on('ifUnchecked', function (event) {
                                                $scope.loggedUser.sale_info = 1;
                                                $http.get(webServer + '/updateSaleDetailInfo/' + $scope.loggedUser.id + '/1' )
                                                    .success(function (result) {

                                                    });
                                            });

                                    }, 1100);
                                }
                            })
                            .error(function(){
                                otherExceptions.sendException("dailySaleInfo",  "Günlük siparişleri çekerken hata döndü!");
                            });
                    }
                }

                $rootScope.$on("MENU_OPENED", function () {
                    if (ispopUpShow) {
                        ispopUpShow = false;
                        $scope.popUpOpen = false;
                    }
                });

                $rootScope.$on("MENU_CLOSED", function () {
                    if (!userAccount.checkUserLoggedin()) {
                        ispopUpShow = true;
                        showPopUp();
                    }
                });

                $scope.popUpClose = function(){
                    $scope.popUpOpen = false;
                };



                function showPopUp() {
                    $timeout(function () {
                        if (!userAccount.checkUserLoggedin() && ispopUpShow) {
                            $scope.popUpOpen = true;
                        }
                    }, 6000);
                }

                var localeLang = translateHelper.getCurrentLang();

                var Status =
                {
                    PREPARING : { id:'1',name:"PREPARING", text:""},
                    ONTHEWAY : { id:'2',name:"ONTHEWAY", text:""},
                    DELIVERED : { id:'3',name:"DELIVERED", text:""},
                    CANCELLED : { id:'4',name:"CANCELLED", text:""}
                };

                for(var i in Status){
                    setStatusText(i);
                }

                function setStatusText(i){
                    translateHelper.getText('PURCHASE_' + Status[i].name, function(statusText){
                        Status[i].text = statusText;
                    });
                }

                $scope.submitNewsletterResponse = function (response){

                    $http.get(webServer + '/updateFBUserNewsletter/' + $scope.loggedUser.id + '/' + response )
                        .success(function (result) {
                            $scope.popUpOpen = false;
                        })
                        .error(function(){
                            $scope.popUpOpen = false;
                            otherExceptions.sendException("dailySaleInfo",  "updateFBUserNewsletter Metodu sırasında hata alındı.");
                        });

                };

                $scope.getDate = function (date) {
                    return moment(date).locale(localeLang).format('DD MMMM YYYY, dddd');
                };

                $scope.getDateOnlyTime = function (date) {
                    return moment(date).locale(localeLang).format('HH:mm');
                };

                $scope.getTime = function (timeStart, timeEnd) {
                    if(timeEnd)
                        return moment(timeStart).locale(localeLang).format('HH:mm') + "-" + moment(timeEnd).locale(localeLang).format('HH:mm');
                    else
                        return moment(timeStart).locale(localeLang).format('HH:mm');
                };

                $scope.checkIsDelivered = function (purchase) {
                    return purchase.delivery_date !== "0000-00-00 00:00:00";
                };

                $scope.getPurchaseStatusText = function (status_id) {
                    for(var i in Status){
                        if(status_id == Status[i].id)
                            return Status[i].text;
                    }
                };

                $scope.facebookLogin = function () {
                    $rootScope.getLoginStatus();
                    $scope.popUpOpen = false;
                }
            }
        };
    });
