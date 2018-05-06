(function () {
    var $ = $ || jQuery,
        locators = {
            templates: '.zh-gallery-template'
        },
        model = {
            path: '/wp-content/plugins/gallery-navigation/',
            // templates: 'templates/',
            assets: 'assets/'
        },
        getTemplateAjax = function (templateName, callback) {
            var source, template;
            $.ajax({
                url: model.path + 'templates/' + templateName + '.hbs',
                dataType: "html",
                success: function (data) {
                    source = data;
                    template = Handlebars.compile(source);
                    if (callback) callback(template);
                }
            });
        },
        getJSONData = function (callback) {
            var jsonData;
            $.ajax({
                url: model.path + 'map.json',
                dataType: "html",
                success: function (data) {
                    jsonData = JSON.parse(data);
                    if (callback) callback(jsonData);
                }
            });
        },
        renderHandlebarsTemplate = function (template, inElement, withData, callback){
            getTemplateAjax(template, function(template) {
                var $targetDiv = (typeof inElement == 'string') ? $(inElement) : inElement ;
                $targetDiv.html(template(withData));
                if (callback) { callback()}
            })
        },
        filterByTag = function (data, tags) {
            var tagsTrimed = _.map(tags.split(","), function (tag) {
                return tag.trim();
            });
            var postObject = _.filter(_.cloneDeep(data), {posts: [{tags: tagsTrimed}]});
            var pageObject = _.filter(_.cloneDeep(data), {tags: tagsTrimed});

            if (!_.isEmpty(postObject)) {
                var postData = _.forEach(postObject, function (page) {
                    page.posts = _.filter(page.posts, function (post) {
                        return _.difference(tagsTrimed, post.tags).length === 0;
                    });
                });
            }
            return _.unionBy(postData, pageObject, "name");
        },

    filterByCategory = function (data, category) {
            return _.filter(data, function (page) {
                return page.name.toLowerCase() == category.toLowerCase();
            })
        },
        doFilters = function (dataToFilter, objectFilter) {
            var data = _.cloneDeep(dataToFilter);
            data = objectFilter.tags ? filterByTag(data, objectFilter.tags) : data;
            data = objectFilter.category ? filterByCategory(data, objectFilter.category) : data;
            return data;
        };
    $(document).ready(function (){
        getJSONData(function (data) {model.header = $(this).data('header');
            $(locators.templates).each(function () {
                var newData = doFilters(data, {
                    category: $(this).data('category'),
                    tags: $(this).data('tags'),
                });
                renderHandlebarsTemplate($(this).data('template'),
                    $(this), {
                        pages: newData,
                        h: model.header,
                        hh: model.header + 1,
                        hhh: model.header + 2,
                    },
                    function () {});
            });

        });
    });
})();
