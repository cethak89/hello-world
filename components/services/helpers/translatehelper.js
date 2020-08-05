'use strict';

/**
 * @ngdoc service
 * @name app.translateHelper
 * @description
 * # translateHelper
 * Service in the bloomNFresh.
 */

angular.module('app')
    .service('translateHelper', function ($translate,$http,$state,otherExceptions) {
        var langs = [];
        var currentLang = "tr";

        this.changeLanguage = function (lang) {

            if ($translate.use() === lang) {
                return false;
            }

            switch (lang) {
                case 'en':
                {
                    if(this.isLanguageAvailable(lang)){
                        currentLang = lang;
                        $translate.use('en');
                    }else{
                        $state.go('landing');
                    }
                    break;
                }
                default :
                {
                    currentLang = lang;
                    $translate.use('tr');
                }
            }
        };

        this.initLanguages = function () {
            if (langs.length > 0)
                return langs;
            else
                return $http.get(webServer + '/get-active-lang')
                    .success(function (result) {
                        langs = result.data;
                    })
                    .error(function () {
                        otherExceptions.sendException("getActiveLang", "Aktif Dilleri Çekerken Serverdan Hata Döndü");
                    });
        };

        this.getActiveLanguages = function(){
            return langs;
        };

        this.isLanguageAvailable = function(language){

             for(var i=0; i< langs.length; i++) {
                 if(langs[i].lang_id === language)
                    return true;
             }

            return false;
        };

        this.getCurrentLang = function(){
            return currentLang;
        };

        this.getText = function(textKey,callback){
            $translate(textKey).then(function(flower_small_header) {
                callback(flower_small_header);
            });
        };
    })
;
