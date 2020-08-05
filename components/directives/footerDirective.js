/**
 * Created by furkan on 20.03.2015.
 */


var footer = angular.module("footerModule",
    [
        'newsSubscriptionModule'
    ]);

footer.directive('bfFooter',function(){
    return{
        restrict: 'E',
        templateUrl: "../../views/main-pages/footer-v2.0.html",
        controller: function(){
            //kissmetricsHelper.outboundClickEvent('.footer-blog',"Footer'dan blog'a tıkladı");
//
            //kissmetricsHelper.clickEvent('.pinterestButton','pinterest butonuna bastı');
            //kissmetricsHelper.clickEvent('.facebookButton','facebook butonuna bastı');
            //kissmetricsHelper.clickEvent('.twitterButton','twitter butonuna bastı');
            //kissmetricsHelper.clickEvent('.instagramButton','instagram butonuna bastı');
        }
    }
});