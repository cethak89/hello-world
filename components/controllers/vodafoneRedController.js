
var vodafoneRedModule = angular.module('vodafoneRed',[
    'ui.mask',
    'ui.validate',
    'PageTagsFactoryModule'
]);

vodafoneRedModule.controller('vodafoneRedCtrl',function($scope,$state,$timeout,$http,analyticsHelper,PageTagsFactory,errorMessages,otherExceptions,$rootScope){
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.contact = {};
    $scope.error = "";
    $scope.working = false;
    PageTagsFactory.changeAndSetVariables();
    analyticsHelper.sendPageView($state.current.name);
    //kissmetricsHelper.sendPageView('Kurumsal Müşteri');

    $rootScope.canonical = 'https://bloomandfresh.com/vodafone-red-kampanyasi';

});