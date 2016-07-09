<div>
    <ul class="uk-breadcrumb">
        <li class="uk-active"><span>@lang('Hugo')</span></li>
    </ul>
</div>

<div class="uk-margin-large-top uk-form" riot-view>

    <h3>@lang('Generate HUGO site') <span class="uk-alert-danger">@lang('Use with caution, still Beta')</span></h3>

    <div class="uk-grid">
        <div class="uk-width-2-3">
            <div class="uk-grid">
                <div class="uk-width-1-3">@lang('Hugo dir'):</div>
                <div class="uk-width-2-3">
                    <a href="#" onclick="{configureHugoDir}"><i class="uk-icon-justify uk-icon-folder"></i></a>
                    <span if="hugoDir">{hugoDir}</span>
                    <span class="uk-alert-danger" if="{!hugoDir}">@lang('PLEASE SET HUGO DIR')<br />
                    @lang('you won\'t be able to generate the files for Hugo unless you set it')</span>


                </div>
            </div>
            <div class="uk-grid uk-margin-small-top">
                <div class="uk-width-1-3">@lang('Generating site for language '):</div>
                <div class="uk-width-2-3"><strong>@lang('default')</strong>
                    <span if="languages.length"> and </span>
                    <strong each="{language in languages}"> {language} </strong>
                </div>
            </div>
        </div>
        <div class="uk-width-1-3">
            <a class="uk-button uk-button-large uk-width-1-1" href="@route('/hugo/fields')"><i class="uk-icon-plus-circle uk-icon-justify"></i>  @lang('Configure Hugo fields')</a>
            <br />
            <p>@lang('Each field in a collection can be mapped to a predefined Hugo field. You can set it here')</p>
        </div>
    </div>


    <div class="uk-grid uk-margin-small-top">
        <div class="uk-flex-item-1">
            @lang('Select collection for which to generate Hugo pages')<br />
            <em>@lang('Each collection will be rendered under a Hugo section, that is a subdir under \'content\'')</em>
        </div>
    </div>
    <div class="uk-grid uk-margin-small-top">
        <div class="uk-width-2-3">
        <fieldset class="" data-uk-margin>
            <div class="uk-form-row" each="{ collection, meta in collections }">
                <input type="checkbox" name="{collection}" checked="{meta.selected}" onclick="{meta.toggle}">  { meta.label || collection }
            </div>
        </fieldset>
        </div>
        <div class="uk-width-1-3">
            <button class="uk-button uk-button-large uk-button-primary " disabled="{ !oneSelected || !hugoDir }" type="button" onclick="{ generate }"><i class="uk-icon-justify uk-icon-arrow-down"></i>@lang('Generate')</button>

        </div>
    </div>

    <h3>@lang('Run Hugo with theme (template)')</h3>
    <div class="uk-grid">
        <div class="uk-width-2-3">
            <i>This command looks for a <em>{hugo_conf_prefix}.{hugo_conf_extension}</em> file for every language you have configured cockpit, in the base Hugo directory, ase set above.</i><br />
            <i>For additional languages the file must be named <em>{hugo_conf_prefix}_XX.{hugo_conf_extension}</em>, where XX can be 'en', 'fr', 'de' etc..</i>
            <br />
            <i>For the time being, this assumes also the <em>{hugo_script}</em> command is in the server's path or is an absolute value.</i><br />

        </div>
        <div class="uk-width-1-3">
            <a class="uk-button uk-button-large uk-width-1-1" href="@route('/hugo/settings')"><i class="uk-icon-gears uk-icon-justify"></i>  @lang('Settings file')</a><br />
            <p> Every value used to run Hugo can be set in the Hugo plugin settings file with this button</p>
        </div>
    </div>
    <div class="uk-grid">
        <div class="uk-width-2-3">
            <div class="uk-grid">
                <div class="uk-width-1-3">@lang('Theme name'):</div>
                <div class="uk-width-2-3">
                    <cp-themeselect alert="@lang('Please select theme')"/>
                    <span if="themeName">
                    </span>
                    <span class="uk-alert-danger" if="{!themeName}">@lang('PLEASE SET HUGO DIR AND THEME NAME')<br />
                    @lang('you won\'t be able to run Hugo and generate HTML unless you set it')</span>
                </div>
            </div>

        </div>
        <div class="uk-width-1-3">
            <button class="uk-button uk-button-large uk-button-primary " disabled="{ !hugoDir || !themeName}" type="button" onclick="{ runHugo }"><i class="uk-icon-justify uk-icon-arrow-down"></i>@lang('Run Hugo')</button>
        </div>
    </div>


    <script type="view/script">

        var $this = this;

        this.ready  = false;
        this.collections = [];
        this.oneSelected=false;
        this.hugoDir='';
        this.themeName='';
        this.languages    = App.$data.languages;

        this.on('mount', function() {

            App.callmodule('collections:collections', true).then(function(data) {

                this.collections = data.result;
                console.log(this.collections);
                //add selection flag
                for(c in this.collections){
                    col=this.collections[c];
                    col['selected']=false;

                    self=this;
                    col.toggle=function(e){
                        col=e.item.meta;
                        col.selected = !col.selected;
                        self.checkCollectionSelected();
                    }
                };

                this.ready  = true;
                this.update();

            }.bind(this));

            App.callmodule('hugo:getHugoSettings',true).then(function(data){
                console.log("HUGOD IR",data.result);
                this.hugoDir=data.result['hugo_base_dir'];
                this.themeName=data.result['hugo_theme'];
                this.hugo_conf_extension=data.result['hugo_conf_extension'];
                this.hugo_conf_prefix=data.result['hugo_conf_prefix'];
                this.hugo_script=data.result['hugo_script'];

                this.update();
                console.log("HUGO THEME",this.themeName);
            }.bind(this));
        });


        checkCollectionSelected(){
            for(c in this.collections){
                col=this.collections[c];
                if(col.selected){
                    this.oneSelected=true;
                    return;
                }
            };
            this.oneSelected=false;
        };
        generate() {
            //get list of collection names
            cols=[];
            for(c in this.collections){
                col=this.collections[c];
                if(col.selected){ 
                    cols.push(c);
                }
            };
            langs=['default'];
            this.languages.forEach(function(l){
                langs.push(l);
            });
            console.log(cols,langs);
            App.request('/hugo/generate', {data:cols, languages:langs }).then(function(data){
                App.ui.notify("Site generated", "success");
                console.log("Generate success:",data);
            },function(err){
                App.ui.notify("Error generating site", "error");
                console.log("Generate error",err);
            });
        }

        runHugo(){
            //check
            if(!this.hugoDir || ! this.themeName){
                console.error("Error, o theme or hugo dir");
            }
            langs=['default'];
            this.languages.forEach(function(l){
                langs.push(l);
            });
            App.request('/hugo/runHugo', { theme:this.themeName, languages:langs}).then(function(data){
                App.ui.notify("Hugo run and site generated", "success");
                console.log("Generate success:",data);
            },function(err){
                App.ui.notify("Error running hugo", "error");
                console.log("Generate error",err);
            });
        }

        test(){
            for(c in this.collections){
                    col=this.collections[c];
                   console.log(col);
                };
 //            console.log("chTHISecl",this);
            console.log("Theme name "+this.themeName);
        }


        configureHugoDir(){
            options  = App.$.extend({
                    previewfiles: false,
                    pattern  : '*',
                    typefilter: '',
                    path: false,
                    selected : []
                }, {});
                var   dialog = UIkit.modal.dialog([
                    '<div>',
                        '<div class="uk-modal-header uk-text-large">Select HUGO Dir</div>',
                        '<cp-dirselect path="'+$this.hugoDir+'"></cp-dirselect>',
                        '<div class="uk-modal-footer uk-text-right">',
                            '<button class="uk-button uk-button-primary uk-margin-right uk-button-large uk-hidden js-select-button">Select dir</button>',
                            '<button class="uk-button uk-button-large uk-modal-close">Close</button>',
                        '</div>',
                    '</div>'
                ].join(''), {modal:false});

                var selectbtn   = dialog.dialog.find('.js-select-button');

                riot.mount(dialog.element[0], '*', options);

                selectbtn.on('click', function() {
                    App.callmodule('hugo:setHugoDir',path).then(function(data){
//console.log("Called setDir, ",$this);
                        $this.hugoDir=path;
                        $this.update();

                    });
                    dialog.hide();
                });
                var path='';
                dialog.on('selectionchange', function(e, s) {
                    isDir = s.selectable ;
                    p = s.path
                    selectbtn[isDir ? 'removeClass':'addClass']('uk-hidden');
                    if(isDir)
                        path=p;
                });

                dialog.show();
        }


    </script>

</div>


