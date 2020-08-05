
var companyCustomerModule = angular.module('companyCustomer',[
    'ui.mask',
    'ui.validate',
    'PageTagsFactoryModule'
]);

companyCustomerModule.controller('companyCustomerCtrl',function($scope,$state,$timeout,$http,analyticsHelper,PageTagsFactory,errorMessages,otherExceptions, $rootScope){
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.contact = {};
    $scope.error = "";
    $scope.working = false;
    PageTagsFactory.changeAndSetVariables();
    analyticsHelper.sendPageView($state.current.name);
    //kissmetricsHelper.sendPageView('Kurumsal Müşteri');


    $rootScope.canonical = 'https://bloomandfresh.com/kurumsal-siparisler';

    $scope.sendMessage = function() {
        if ( $(companyCustomerForm.companyCompany).hasClass('ng-valid') &&  $(companyCustomerForm.contactName).hasClass('ng-valid') && $(companyCustomerForm.contactMail).hasClass('ng-valid') && $(companyCustomerForm.contactMessage).hasClass('ng-valid'))
        {
            $scope.working = true;
            var data = {
                name: $scope.contact.name,
                surname: $scope.contact.name,
                email: $scope.contact.email,
                message: $scope.contact.contactMessage,
                company: $scope.contact.company
            };

            if($scope.contact.mobile)
                data.mobile = $scope.contact.mobile;

            $http.post(webServer +'/insert-company-request',data)
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
            if (!($(companyCustomerForm.contactName).hasClass('ng-valid')))
                otherExceptions.sendException("contact us", "Eksik Bilgi- isim");
            else if (!($(companyCustomerForm.contactMail).hasClass('ng-valid')))
                otherExceptions.sendException("contact us", "Eksik Bilgi- mail");
            else if (!($(companyCustomerForm.contactMessage).hasClass('ng-valid')))
                otherExceptions.sendException("contact us", "Eksik Bilgi- mesaj");
            else
                otherExceptions.sendException("contact us", "Eksik Bilgi");
            $scope.isChecked = true;
        }
    }
});