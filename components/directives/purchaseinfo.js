'use strict';

/**
 * @ngdoc directive
 * @name bloomNFresh.directive:purchaseInfo
 * @description
 * # purchaseInfo
 */
angular.module('purchase')
    .directive('purchaseInfo', function () {
        return {
            templateUrl: '../../views/bf-utility-pages/purchaseInfo-v2.0.html',
            restrict: 'E',
            scope: {
                flower: "=",
                campaign: "=",
                section: "=",
                receiver: "=?",
                note: "=?"
            },
            controller: function ($scope, $rootScope,momentHelper, $state) {
                //console.log(campaign);
                console.log($scope.$parent.tempStringTroy);

                $scope.isInfoShowable = function (infoSection) {
                    //console.log( $rootScope.chosenCrossProduct);
                    if($scope.section == 2.5)
                        return true;
                    return $scope.section >= infoSection;
                };

                $scope.showSkipButton = function () {
                    $scope.selectedCrossSell = $rootScope.chosenCrossProduct;
                    return ($scope.section == 2.5 && !$scope.selectedCrossSell);
                };

                $scope.showRemoveButton = function(){
                    return ($scope.section != 2.5 && $scope.selectedCrossSell);
                };

                $scope.removeCrossSell = function(){
                    $rootScope.chosenCrossProduct = null;

                    if(  parseFloat( $scope.$parent.flower.newPrice )  < 100 || !$rootScope.tempStringTroyRoot ){
                        $scope.$parent.tempStringTroy = '';
                    }
                    else{
                        $scope.$parent.tempStringTroy = ' - TROY indirimi 30 TL';
                        console.log($scope.$parent.flower.newPrice);
                        $scope.$parent.troyPrice =  parseFloat( $scope.$parent.flower.newPrice  ) - 30;
                    }

                    $rootScope.tempStringTroyRoot = $scope.$parent.tempStringTroy;
                };

                $scope.showCrossSellProduct = function () {
                    if($rootScope.chosenCrossProduct){
                        return true;
                    }
                    else
                        return false;
                };

                $scope.skipCrossSell = function(){
                    $state.go('purchaseProcess', {'purchaseStep': 'odeme-bilgileri'});
                };

                /***  date functions **/
                $scope.getReceiverDate = function () {

                    if ($scope.receiver !== undefined && $scope.receiver.date !== undefined)
                        return momentHelper.getTime('D MMM YYYY, dddd', moment($scope.receiver.date.value, 'DD-MM-YYYY'));
                };

                $scope.getReceiverTime = function () {

                    if($scope.flower.sendingDistrict.continent_id == 'Ups'){
                        return '';
                    }

                    if ($scope.receiver !== undefined && $scope.receiver.date !== undefined && $scope.receiver.time !== undefined) {
                        return $scope.receiver.time.start_hour + " - " + $scope.receiver.time.end_hour;
                    }
                };

                $scope.takeReceiverText = function(){
                    if( $scope.flower.sendingDistrict.continent_id == 'Ups' ){
                        return 'Kargo Tarihi';
                    }
                    else{
                        return 'Gönderim Tarihi';
                    }
                };

                $scope.showWithDiscount = function(){
                    return replaceString(parseFloat( $scope.flower.newPrice - 30 ).toFixed(2),'.', ',');
                };

                $scope.showWithOutDiscount = function(){
                    return replaceString(parseFloat( $scope.flower.newPrice - 30 ).toFixed(2),'.', ',');
                };
            }
        };
    });
