

var contactUsModule = angular.module('contactUs',[
    'ui.mask',
    'ui.validate',
    'PageTagsFactoryModule'
]);

contactUsModule.controller('ContactUsCtrl',function($scope,$state,$timeout,$http,analyticsHelper,PageTagsFactory,errorMessages,otherExceptions,$rootScope){
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.contact = {};
    $scope.error = "";
    $scope.working = false;

    $rootScope.canonical = 'https://bloomandfresh.com/bize-ulasin';

    PageTagsFactory.changeAndSetVariables();
    analyticsHelper.sendPageView($state.current.name);
    //kissmetricsHelper.sendPageView('Bize ulaşın');

    $scope.sendMessage = function() {
        if ($(contactUsForm.contactName).hasClass('ng-valid') && $(contactUsForm.contactMail).hasClass('ng-valid') && $(contactUsForm.contactMessage).hasClass('ng-valid'))
        {
            $scope.working = true;
            var data = {
                name: $scope.contact.name,
                surname: $scope.contact.name,
                email: $scope.contact.email,
                message: $scope.contact.contactMessage
            };

            if($scope.contact.mobile)
                data.mobile = $scope.contact.mobile;

            $http.post(webServer +'/insert-messages',data)
                .success(function()
                {
                    $scope.errorMessage = "";
                    $scope.processSuccess = true;
                    $timeout(function(){
                        $scope.working = false;
                    },1000)
                })
                .error(function(response){
                    errorMessages.getErrorMessage(response.description, function (errorMessage) {
                        $scope.error = errorMessage;
                        $scope.working = false;
                    });
                    //$scope.error = response.description;
                });

        }else
        {

            if (!($(contactUsForm.contactName).hasClass('ng-valid')))
                otherExceptions.sendException("contact us", "Eksik Bilgi- isim");
            else if (!($(contactUsForm.contactMail).hasClass('ng-valid')))
                otherExceptions.sendException("contact us", "Eksik Bilgi- mail");
            else if (!($(contactUsForm.contactMessage).hasClass('ng-valid')))
                otherExceptions.sendException("contact us", "Eksik Bilgi- mesaj");
            else
                otherExceptions.sendException("contact us", "Eksik Bilgi");
            $scope.isChecked = true;
        }
    }
});