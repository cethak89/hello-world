var rescuePassword = angular.module("rescuePasswordModule",
    [
        'menuModule',
        'footerModule',
        'PageTagsFactoryModule',
        'userAccountModule'
    ]
);

rescuePassword.controller("rescuePasswordCtrl", function ($scope,analyticsHelper,$stateParams,$state,$timeout,PageTagsFactory,userAccount, $rootScope) {
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.userPasswordModel = {};
    $scope.errorMessage = "";

    if($stateParams.userId === undefined || $stateParams.token === undefined){
        $state.go('landing');
    }else{
        PageTagsFactory.setTags("");
        PageTagsFactory.changeWebSiteVariable();
        analyticsHelper.sendPageView('/passwordRetrieval');
        //kissmetricsHelper.sendPageView("şifre kurtarmayı");

        $scope.rescuePassword = function(){
            if ($(rescuePasswordForm.changePassword).hasClass('ng-valid') && $(rescuePasswordForm.changePasswordCheck).hasClass('ng-valid')) {
                userAccount.rescuePassword($stateParams.userId,$stateParams.token, $scope.userPasswordModel.changePassword, function (result, errorMessage) {
                    if (result) {
                        $scope.processSuccess = true;
                        $timeout(function () {
                            $state.go('landing');
                        }, 1000);
                    } else {
                        $scope.processSuccess = false;
                        $scope.isChecked = true;
                        $scope.errorMessage = 'Bir hata gerçekleşti.';
                    }
                });
            } else {
                $scope.isChecked = true;
            }
        }
    }
});