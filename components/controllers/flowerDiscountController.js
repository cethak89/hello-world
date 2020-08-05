
var flowerDiscountModule = angular.module('flowerDiscount',[
    'PageTagsFactoryModule'
]);

flowerDiscountModule.controller('flowerDiscountCtrl',function($scope,$state,$timeout,$http,analyticsHelper,PageTagsFactory,errorMessages,otherExceptions, $rootScope){
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.contact = {};
    $scope.error = "";
    $scope.working = false;
    PageTagsFactory.changeAndSetVariables();
    analyticsHelper.sendPageView($state.current.name);
    //kissmetricsHelper.sendPageView('Kurumsal Müşteri');

    $rootScope.canonical = 'https://bloomandfresh.com/kurumsal-siparisler';

});