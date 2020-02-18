'use strict'

app
  .factory('LoadingListener', function ($q, $rootScope) {
    let reqsActive = 0;
    function onResponse () {
      reqsActive--;
      if (reqsActive === 0) {
        $rootScope.$broadcast('loading:completed');
      }
    }

    return {
      'request': function (config) {
        if (reqsActive === 0) {
          $rootScope.$broadcast('loading:started')
        }
        reqsActive++
        return config
      },
      'response': function (response) {
        if (!response || !response.config) {
          return response
        }
        onResponse()
        return response
      },
      'responseError': function (rejection) {
        if (!rejection || !rejection.config) {
          return $q.reject(rejection)
        }
        onResponse()
        return $q.reject(rejection)
      },
      'isLoadingActive': function () {
        return reqsActive === 0
      }
    };
  })
  .directive('loadingListener', function ($rootScope, LoadingListener) {
    let tpl = '<div class="mr-waper-loader">\n' +
      '    <div class="loader-circle-full-screen"></div>\n' +
      '</div>'
    return {
      restrict: 'CA',
      link: function linkFn (scope, elem, attr) {
        let indicator = angular.element(tpl);
        elem.prepend(indicator);

        elem.css('position', 'relative');
        if (!LoadingListener.isLoadingActive()) {
          indicator.css('display', 'none');
        }

        $rootScope.$on('loading:started', function () {
          indicator.css('display', 'block');
        })
        $rootScope.$on('loading:completed', function () {
          indicator.css('display', 'none');
        })
      }
    }
  });
