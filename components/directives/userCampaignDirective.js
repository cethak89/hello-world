'use strict';

/**
 * @ngdoc directive
 * @name bloomNFresh.directive:userCampaignDirective
 * @description
 * # userCampaignDirective
 */
angular.module('menuModule')
    .directive('userCampaign', function (otherExceptions) {
        return {
            restrict: 'E',
            controller: function ($scope, $rootScope, $timeout, errorMessages) {
                $scope.addCampaign = false;
                $scope.isChecked = false;
                $scope.campaign = {};
                $scope.errorMessage = "";

                $scope.checkContainer = function (index) {
                    return (index + 1) % 3 === 0;
                };

                $scope.isCampaignExist = function () {
                    if ($scope.campaigns)
                        return $scope.campaigns.length > 0;
                    else
                        return false;
                };

                $scope.useCampaign = function (campaign) {
                    $timeout(function () {
                        $rootScope.$broadcast('SEND_FLOWER_WITH_CAMPAIGN', campaign);
                    }, 500);
                    $scope.setSignSection('');
                };

                $scope.changeAddCampaign = function () {
                    $scope.addCampaign = !$scope.addCampaign;
                };

                $scope.saveCampaign = function () {
                    if ($(addCampaignForm.campaignId).hasClass('ng-valid')) {
                        $scope.isChecked = false;
                        $scope.loggedUser.campaigns.addCampaign($scope.campaign.id, $scope.loggedUser.access_token,
                            function (result, errorMessage) {
                                if (result) {
                                    //$timeout(function () {
                                        $scope.changeAddCampaign();
                                        $scope.loggedUser.campaigns.initCampaigns($scope.loggedUser.access_token,
                                            function (result, errorMessage) {
                                                if (result) {
                                                    //$timeout(function () {
                                                    $scope.campaigns = $scope.loggedUser.campaigns.getCampaigns();
                                                    //}, 200);
                                                }
                                            });
                                        //$scope.campaigns = $scope.loggedUser.campaigns.getCampaigns();
                                    //}, 200);
                                }
                                else {
                                    errorMessages.getErrorMessage(errorMessage, function(errorMessage){
                                        $scope.processSuccess = false;
                                        $scope.isChecked = true;
                                        $scope.working = false;
                                        $scope.errorMessage = errorMessage;
                                    });
                                    //$scope.errorMessage = errorMessage;
                                }
                            });
                    } else {
                        $scope.isChecked = true;
                        otherExceptions.sendException("add Campaign-profil", "Eksik Bilgi");
                    }
                };
            },
            templateUrl: '../../views/menu-views/userCampaignsTab-v2.0.html'
        };
    });
