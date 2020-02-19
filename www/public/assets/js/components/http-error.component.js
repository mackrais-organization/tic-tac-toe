'use strict'

app
  .factory('HttpErrorListener', function ($q, $rootScope) {
    return {
      'responseError': function (rejection) {
        $rootScope.httpError = rejection;
        $rootScope.$broadcast('error:show');
        return $q.reject(rejection)
      }
    };
  })
  .directive('errorBlock', function ($rootScope) {
    return {
      restrict: 'CA',
      'template': '<div ng-show="show" class="alert alert-danger alert-dismissible fade show" role="alert">\n' +
        '<strong ng-bind="errorCode"></strong> <span ng-bind="errorText"></span>' +
        '  <button type="button" class="close" data-dismiss="alert" aria-label="Close" ng-click="close()">\n' +
        '    <span aria-hidden="true">&times;</span>\n' +
        '  </button>' +
        '</div>',

      link: ($scope) =>  {
        $scope.show = false;
        $scope.errorCode = null;
        $scope.errorText = null;

        $rootScope.$on('error:show',  () => {
          $scope.show = true;
          $scope.errorCode = $rootScope.httpError.status;
          $scope.errorText = $rootScope.httpError.statusText;
          if($rootScope.httpError.hasOwnProperty('data') && $rootScope.httpError.data.hasOwnProperty('message')){
            $scope.errorText = $rootScope.httpError.data.message;
          }
        });

        $scope.close = () => $scope.show = false;
      }
    }
  });
