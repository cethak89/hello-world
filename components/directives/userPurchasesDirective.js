'use strict';

/**
 * @ngdoc directive
 * @name menuModule.directive:userPurchasesDirective
 * @description
 * # userPurchasesDirective
 */
angular.module('menuModule')
    .directive('userPurchases', function () {
        return {
            restrict: 'E',
            controller: function ($scope,translateHelper) {
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

                $scope.getDate = function (date) {
                    return moment(date).locale(localeLang).format('DD MMMM YYYY, dddd');
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

                $scope.isPurchasesExist = function () {
                    if ($scope.purchases)
                        return $scope.purchases.length > 0;
                    else
                        return false;
                };

                $scope.getPurchaseStatusText = function (status_id) {
                    for(var i in Status){
                        if(status_id == Status[i].id)
                            return Status[i].text;
                    }
                };
            },
            templateUrl: '../../views/menu-views/userPurchasesTab-v2.0.html'
        };
    });
