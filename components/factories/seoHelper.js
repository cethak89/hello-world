/**
 * Created by furkan on 20.05.2015.
 */
var module = angular.module('PageTagsFactoryModule',[]);

module.factory('PageTagsFactory', function ($rootScope,$location,$state, translateHelper) {
    var tags = {};
    tags.title = "Stil sahibi çiçekler: Taze, hızlı, senin: Bloom and Fresh";
    tags.description = "İstanbul'un stil sahibi, taze ve hızlı çiçekleri Bloom and Fresh";
    tags.url = location.href;
    tags.imgUrl = "https://bloomandfresh.com/images/promos/bloomandfresh-header-promo.png";

    var SEOELEMENTS = {
        'landing': {
            tr: {
                //title: "İstanbul'da online çiçek göndermenin en şık yolu! İstanbul’un stil sahibi çiçekleri Bloom and Fresh ile en özel çiçekleri %10 Yeni Üye indirimi ve aynı gün ücretsiz teslimat ile online sipariş verebilirsin!",
                title: "Bloom and Fresh: Online Çiçek, Çikolata ve Hediye Gönder!",
                //description: "İstanbul'da online çiçek göndermenin en şık yolu! İstanbul’un stil sahibi çiçekleri Bloom and Fresh ile en özel çiçekleri %10 Yeni Üye indirimi ve aynı gün ücretsiz teslimat ile online sipariş verebilirsin!"
                description: "Çiçek göndermek veya leziz bir çikolata hediye etmek! Online siparişi ile aynı gün teslim eşsiz butik çiçek, çikolata ve hediyeleri sevdiklerinize hediye edin!"
            },
            en: {
                //title: "İstanbul'da online çiçek göndermenin en şık yolu! İstanbul’un stil sahibi çiçekleri Bloom and Fresh ile en özel çiçekleri %10 Yeni Üye indirimi ve aynı gün ücretsiz teslimat ile online sipariş verebilirsin!",
                title: "Bloom and Fresh: Online Çiçek, Çikolata ve Hediye Gönder!",
                //description: "İstanbul'da online çiçek göndermenin en şık yolu! İstanbul’un stil sahibi çiçekleri Bloom and Fresh ile en özel çiçekleri %10 Yeni Üye indirimi ve aynı gün ücretsiz teslimat ile online sipariş verebilirsin!"
                description: "Çiçek göndermek veya leziz bir çikolata hediye etmek! Online siparişi ile aynı gün teslim eşsiz butik çiçek, çikolata ve hediyeleri sevdiklerinize hediye edin!"
            }
        },
        'flowers' : {
            tr: {
                //title: "İstanbul'da online çiçek göndermenin en şık yolu! İstanbul’un stil sahibi çiçekleri Bloom and Fresh ile en özel çiçekleri %10 Yeni Üye indirimi ve aynı gün ücretsiz teslimat ile online sipariş verebilirsin!İstanbul'da online çiçek göndermenin en şık yolu! İstanbul’un stil sahibi çiçekleri Bloom and Fresh ile en özel çiçekleri %10 Yeni Üye indirimi ve aynı gün ücretsiz teslimat ile online sipariş verebilirsin!İstanbul'da online çiçek göndermenin en şık yolu! İstanbul’un stil sahibi çiçekleri Bloom and Fresh ile en özel çiçekleri %10 Yeni Üye indirimi ve aynı gün ücretsiz teslimat ile online sipariş verebilirsin!",
                title: "Bloom and Fresh ile online çiçek gönder!",
                description: "İnternetten çiçek göndermenin en şık yolu Bloom and Fresh çiçekleri!"
            },
            en : {
                //title: "İstanbul'da online çiçek göndermenin en şık yolu! İstanbul’un stil sahibi çiçekleri Bloom and Fresh ile en özel çiçekleri %10 Yeni Üye indirimi ve aynı gün ücretsiz teslimat ile online sipariş verebilirsin!",
                title: "Bloom and Fresh ile online çiçek gönder!",
                description: "İnternetten çiçek göndermenin en şık yolu Bloom and Fresh çiçekleri!"
            }
        },
        'aboutUs' : {
            tr: {
                title: "Bloom and Fresh - Ekibimiz Hakkında",
                description: "Bloom and Fresh'in en iyi online çiçek gönderme alternatifi olması için çalışan ekibimiz"
            },
            en : {
                title: "Bloom and Fresh - The team",
                description: "Bloom and Fresh - The team and the people"
            }
        },
        'how' : {
            tr: {
                title: "Bloom and Fresh ve Çiçekleri Hakkında",
                description: "Çiçek göndermenin en şık yolu Bloom and Fresh hakkında bilmek istediklerin burada."
            },
            en : {
                title: "Bloom and Fresh ve Çiçekleri Hakkında",
                description: "Çiçek göndermenin en şık yolu Bloom and Fresh hakkında bilmek istediklerin burada."
            }
        },
        'contactUs' : {
            tr: {
                title: "Bloom and Fresh - Çiçeklere ve ekibimize ulaşın",
                description: "Bloom and Fresh ve İstanbul çiçek siparişleri ile ilgili tüm soruların için bize ulaşabilirsin."
            },
            en : {
                title: "Bloom and Fresh - Contact us for flowers",
                description: "Contact us for anything related to flowers and Bloom and Fresh"
            }
        },
        'help' : {
            tr: {
                title: "Bloom And Fresh Destek",
                description: "İstanbul'un Online Çiçekçisi Bloom and Fresh, Sipariş Ve Teslimat Destek"
            },
            en : {
                title: "Bloom and Fresh Support",
                description: "Support for online flower orders and deliveries in İstanbul"
            }
        },
        'contracts' : {
            tr: {
                title: "Bloom And Fresh Hukumler Ve Kosullar",
                description: "Bloom and Fresh'de ki hukumler ve kosullar"
            },
            en : {
                title: "Terms & Conditions",
                description: "Terms & Conditions for Bloom and Fresh"
            }
        },
        'cikolatPage' :  {
            tr: {
                title: "Çikolata Ve Çiçek Göndermek İsteyenler İçin Online Godiva Çikolata Siparişi!",
                description: "Çiçek gönderirken yanında çikolata da göndermek istersen Godiva çikolata seçenekleri Bloom and Fresh'te"
            },
            en : {
                title: "Çikolata Ve Çiçek Göndermek İsteyenler İçin Online Godiva Çikolata Siparişi!",
                description: "Çiçek gönderirken yanında çikolata da göndermek istersen Godiva çikolata seçenekleri Bloom and Fresh'te"
            }
        },
        'locationPage' :  {
            tr: {
                title: "İstanbul Çiçek Siparişi - İstanbul İçi Online Çiçek Gönder",
                description: "İstanbul ve ilçelerine aynı gün teslimat ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            },
            en : {
                title: "İstanbul Çiçek Siparişi - İstanbul İçi Online Çiçek Gönder",
                description: "İstanbul ve ilçelerine aynı gün teslimat ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            }
        },
        'locationPageAnk' :  {
            tr: {
                title: "Ankara Çiçek Siparişi - Ankara İçi Online Çiçek Gönder",
                description: "Ankara ve ilçelerine aynı gün teslimat ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            },
            en : {
                title: "Ankara Çiçek Siparişi - Ankara İçi Online Çiçek Gönder",
                description: "Ankara ve ilçelerine aynı gün teslimat ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            }
        },
        'locationPageBursa' :  {
            tr: {
                title: "Bursa Çiçek Siparişi - Bursa İçi Online Çiçek Gönder",
                description: "Bursa ve ilçelerine aynı gün kargo ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            },
            en : {
                title: "Bursa Çiçek Siparişi - Bursa İçi Online Çiçek Gönder",
                description: "İstanbul ve ilçelerine aynı gün kargo ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            }
        },
        'locationPageAntalya' :  {
            tr: {
                title: "Antalya Çiçek Siparişi - Antalya İçi Online Çiçek Gönder",
                description: "Antalya ve ilçelerine aynı gün kargo ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            },
            en : {
                title: "Antalya Çiçek Siparişi - Antalya İçi Online Çiçek Gönder",
                description: "Antalya ve ilçelerine aynı gün kargo ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            }
        },
        'locationPageIzmir' :  {
            tr: {
                title: "İzmir Çiçek Siparişi - İzmir İçi Online Çiçek Gönder",
                description: "İzmir ve ilçelerine aynı gün kargo ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            },
            en : {
                title: "İzmir Çiçek Siparişi - İzmir İçi Online Çiçek Gönder",
                description: "İzmir ve ilçelerine aynı gün kargo ile online çiçek sipariş etmek için Bloom and Fresh'in birbirinden şık çiçek aranjmanlarına göz atın."
            }
        }
    };

    var getDefaultSeoVariables = function () {
        setPageTags("İstanbul'un Stil Sahibi Online Çiçekleri: Bloom and Fresh", "İstanbul'da çiçek göndermenin en iyi adresi!");
    };

    var getFlowerDescriptionVariables = function(flowerObj){
        setPageTags(flowerObj.url_title,flowerObj.meta_description,flowerObj.MainImage);
    };

    var getFlowersVariables = function(pageTitle, tagName, meta_desc){
        //var description = meta_desc;
        //description = tagName !== undefined ? description + ": " + tagName : description;
        //console.log(pageTitle);
        //console.log(meta_desc);

        if(meta_desc == undefined){
            tagName = 'Bloom and Fresh ile online çiçek gönder!';
            meta_desc = 'İnternetten çiçek göndermenin en şık yolu Bloom and Fresh çiçekleri!';
        }

        setPageTags(tagName,meta_desc);
    };

    var setPageTags = function(title, description, img){
        tags.title = title;
        tags.description = description;
        tags.imgUrl = img === undefined ? "https://bloomandfresh.com/images/promos/bloomandfresh-header-promo.png" : img;
        tags.url = $location.absUrl();
    };

    var setTags = function(){
        switch ($state.current.name){
            case 'flowerDescription':{
                getFlowerDescriptionVariables(arguments[0]);
                break;
            }
            case 'flowers':{
                getFlowersVariables(arguments[0],arguments[1],arguments[2]);
                break
            }
            default :{
                var page = SEOELEMENTS[$state.current.name];
                var currentLang = translateHelper.getCurrentLang();

                if(page)
                    setPageTags(page[currentLang].title, page[currentLang].description);
                else
                    getDefaultSeoVariables();
            }

        }
    };

    var changeWebSiteVariable = function(){
        $rootScope.title = tags.title;
        $rootScope.description = tags.description;
        $rootScope.url_link = tags.url;
        //console.log($rootScope);
        $rootScope.imgUrl = tags.imgUrl;
    };

    return{
        getTitle : function() {
            return tags.title;
        },
        getDescription: function(){
            return tags.description;
        },
        getUrls: function(){
            return tags.url
        },
        setTags : setTags,
        changeWebSiteVariable: changeWebSiteVariable,
        changeAndSetVariables: function(){
            setTags();
            changeWebSiteVariable();
        }
    }
});

/****       constant variables                    *****/
function getWindowOrigin() {
    return window.location.protocol + "//" + window.location.host
}