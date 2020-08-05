/**
 * Created by furkan on 25.03.2015.
 */

var newsSubscriptionModule = angular.module("newsSubscriptionModule",
    [
        'newsSubscriptionService'
    ]
);

newsSubscriptionModule.directive('bfNewsSubscription', function () {
    return {
        restrict: 'E',
        templateUrl: '../../views/bf-utility-pages/newsSubscription-v2.0.html',
        controller: function ($scope, newsLetter, otherExceptions, translateHelper, errorMessages) {
            $scope.IsSuccess = false;
            $scope.IsError = false;
            var mailFilter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;

            $scope.addNewFollower = function () {
                if ($scope.subscription !== undefined && $scope.subscription.mail !== undefined && $scope.subscription.mail !== "") {
                    if (mailFilter.test($scope.subscription.mail)) {
                        newsLetter.addNewUserToList($scope.subscription.mail,
                            function (result, errorCode) {
                                if (result) {
                                    $scope.IsSuccess = true;
                                    $scope.IsError = false;
                                    //kissmetricsHelper.recordEvent('E-posta adresi bıraktı');
                                    setTimeout(function () {
                                        $scope.IsSuccess = false;
                                        $scope.subscription.mail = "";
                                        $scope.$apply();
                                    }, 2000);
                                }
                                else {
                                    errorMessages.getErrorMessage(errorCode, function (errorMessage) {
                                        $scope.IsError = true;
                                        $scope.IsSuccess = false;
                                        $scope.errorMessage = errorMessage;
                                        setTimeout(function () {
                                            $scope.errorMessage = "";
                                            $scope.IsError = false;
                                            $scope.$apply();
                                        }, 2000);
                                    });
                                }
                            });
                    }
                    else {
                        translateHelper.getText('EMAIL_ERROR', function (emailError) {
                            $scope.IsError = true;
                            $scope.IsSuccess = false;
                            $scope.errorMessage = emailError;
                            otherExceptions.sendException("newsSubscription", "Yanlış e-posta");
                            setTimeout(function () {
                                $scope.errorMessage = "";
                                $scope.IsError = false;
                                $scope.$apply();
                            }, 2000);
                        });
                    }
                } else {
                    translateHelper.getText('EMAIL_EMPTY_ERROR', function (emailError) {
                        $scope.IsError = true;
                        $scope.IsSuccess = false;
                        $scope.errorMessage = emailError;
                        otherExceptions.sendException("newsSubscription", "Eksik Bilgi");
                        setTimeout(function () {
                            $scope.errorMessage = "";
                            $scope.IsError = false;
                            $scope.$apply();
                        }, 2000);
                    });
                }
            }
        }
    }
});