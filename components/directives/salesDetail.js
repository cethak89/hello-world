'use strict';

/**
 * @ngdoc directive
 * @name app.directive:rightBottomPopUp
 * @description
 * # rightBottomPopUp
 */
angular.module('landingModule')
    .directive('saleDetailPopUp', function () {
        return {
            templateUrl : "../../views/bf-utility-pages/saleDetailPopup.html",
            restrict: 'E',
            controller: function ($scope, $rootScope, $timeout, userAccount) {
                $scope.popUpOpen = false;
                var ispopUpShow = true;
                showPopUp();
                console.log('Test');

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

                function showPopUp() {
                    $timeout(function () {
                        if (userAccount.checkUserLoggedin() && ispopUpShow) {
                            $scope.popUpOpen = true
                        }
                    }, 2000);
                }

                $scope.facebookLogin = function () {
                    $rootScope.getLoginStatus();
                    $scope.popUpOpen = false;
                }
            }
        };
    });
