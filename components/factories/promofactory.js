'use strict';

/**
 * @ngdoc service
 * @name app.promoFactory
 * @description
 * # promoFactory
 * Factory in the app.
 */
angular.module('app')
    .factory('promoFactory', function ($http,$window, otherExceptions, translateHelper) {
        $http.defaults.headers.post["Content-Type"] = "application/json";

        var promos = [];
        var landingPromos = [];

        var initPromos = $http.get(webServer + '/get-banners/'+ translateHelper.getCurrentLang() + "/" +isMobile() )
            .success(function (result) {
                promos = result.data;
            })
            .error(function(){
                otherExceptions.sendException("promoFactory",  "Bannerları Çekerken Serverdan Hata Döndü");
            });


        var initLandingPromos = $http.get(webServer + '/get-landing-promo-and-flower' )
            .success(function (result) {
                landingPromos = result.data;
            })
            .error(function(){
                otherExceptions.sendException("promoFactory",  "Bannerları Çekerken Serverdan Hata Döndü");
            });

        function isMobile(){
            return window.innerWidth <= 767 ? 1 : 0;
        }
        
        return {
            getPromos : function () {
                if(promos.length > 0)
                    return promos;
                else
                    return initPromos;
            },
            initPromos: initPromos,
            getLandingPromos : function () {
                if(landingPromos.length > 0)
                    return landingPromos;
                else
                    return initLandingPromos;
            },
            initLandingPromos: initLandingPromos
        };
    });
