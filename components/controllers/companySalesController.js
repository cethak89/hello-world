
var companySalesModule = angular.module('companySales',[
    'ui.mask',
    'ui.validate',
    'PageTagsFactoryModule'
]);

companySalesModule.controller('companySalesController',function(userAccount,$scope,$state,$timeout,$http,analyticsHelper,PageTagsFactory,errorMessages,otherExceptions, $rootScope){
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.contact = {};
    $scope.error = "";
    $scope.working = false;
    PageTagsFactory.changeAndSetVariables();
    analyticsHelper.sendPageView($state.current.name);
    //kissmetricsHelper.sendPageView('Kurumsal Müşteri');

    $rootScope.canonical = 'https://bloomandfresh.com/company-user-sales';

    $timeout(function () {
        var data = {
            access_token : $scope.loggedUser.access_token
        };

        $http.post(webServer +'/get-company-sales-list',data)
            .success(function (data) {
                $scope.sales = data.sales;
            })
            .error(function() {
                otherExceptions.sendException("Sirket siparisleri", "Bölgeleri Çekerken Serverdan Hata Döndü");
            });
    }, 1000);


});