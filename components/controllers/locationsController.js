
var locationsModule = angular.module('locations',[
    'ui.mask',
    'ui.validate',
    'PageTagsFactoryModule'
]);

locationsModule.controller('locationsCtrl',function($scope,$state,$timeout,$http,analyticsHelper,PageTagsFactory,errorMessages,otherExceptions, $rootScope){
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.contact = {};
    $scope.error = "";
    $scope.working = false;
    PageTagsFactory.changeAndSetVariables();
    analyticsHelper.sendPageView($state.current.name);
    //kissmetricsHelper.sendPageView('Kurumsal Müşteri');

    if( $state.current.name == 'locationPageAnk' ){
        $rootScope.canonical = 'https://bloomandfresh.com/ankara-cicek-siparisi';

        $http.get(webServer + '/location-list-ank')
            .success(function (data) {
                $scope.locationsAnk = data.locations;
            })
            .error(function () {
                otherExceptions.sendException("deviveryLocations", "Bölgeleri Çekerken Serverdan Hata Döndü");
            });

    }
    else if( $state.current.name == 'locationPageBursa' ){
        $rootScope.canonical = 'https://bloomandfresh.com/bursa-cicek-siparisi';

        $http.get(webServer + '/location-list-ups/Bursa')
            .success(function (data) {
                $scope.locationsUps = data.locations;
            })
            .error(function () {
                otherExceptions.sendException("deviveryLocations", "Bölgeleri Çekerken Serverdan Hata Döndü");
            });

    }
    else if( $state.current.name == 'locationPageIzmir' ){
        $rootScope.canonical = 'https://bloomandfresh.com/izmir-cicek-siparisi';

        $http.get(webServer + '/location-list-ups/İzmir')
            .success(function (data) {
                $scope.locationsUps = data.locations;
            })
            .error(function () {
                otherExceptions.sendException("deviveryLocations", "Bölgeleri Çekerken Serverdan Hata Döndü");
            });

    }
    else if( $state.current.name == 'locationPageAntalya' ){
        $rootScope.canonical = 'https://bloomandfresh.com/antalya-cicek-siparisi';

        $http.get(webServer + '/location-list-ups/Antalya')
            .success(function (data) {
                $scope.locationsUps = data.locations;
            })
            .error(function () {
                otherExceptions.sendException("deviveryLocations", "Bölgeleri Çekerken Serverdan Hata Döndü");
            });

    }
    else{
        $rootScope.canonical = 'https://bloomandfresh.com/istanbul-cicek-siparisi';

        $http.get(webServer + '/location-list')
            .success(function (data) {
                $scope.locations = data.locations;
            })
            .error(function () {
                otherExceptions.sendException("deviveryLocations", "Bölgeleri Çekerken Serverdan Hata Döndü");
            });

    }


});