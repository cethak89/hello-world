
var deliveryLocationModule = angular.module('deliveryLocations',[
    'ui.mask',
    'ui.validate',
    'PageTagsFactoryModule'
]);

deliveryLocationModule.controller('deliveryLocationCtrl',function($scope,$state,$timeout,$http,analyticsHelper,PageTagsFactory,errorMessages,otherExceptions, $rootScope){
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.contact = {};
    $scope.error = "";
    $scope.working = false;
    PageTagsFactory.changeAndSetVariables();
    analyticsHelper.sendPageView($state.current.name);
    //kissmetricsHelper.sendPageView('Dağıtım Bölgeleri');


    $rootScope.canonical = 'https://bloomandfresh.com';

});