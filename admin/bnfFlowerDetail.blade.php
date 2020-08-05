@extends('app2')
@section('content')
<header class="otherPages">
	<left-hidden-menu></left-hidden-menu>

	<right-menus></right-menus>

	<nav class="navbar navbar-default">
		<section style="background-color: white;" class="container-fluid">
			<ul class="nav navbar-nav navbar-left pull-left">
				<li>
					<button type="button" class="secretMenu btn btn-default btn-lg" aria-label="Left Align">
						<span class="glyphicon ion-navicon lightBrownText" aria-hidden="true"></span>
					</button>
				</li>
			</ul>

			<ul class="nav navbar-nav navbar-right pull-right">
				<li>
					<button type="button" class="signIn btn btn-default btn-lg pull-right" aria-label="Right Align">
						<span class="signInIcon signInBrownIcon lightBrownText" aria-hidden="true"></span>
					</button>
				</li>
			</ul>

			<a href="/testLanding" class="center-block">
				<img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/logos/bloomandfresh_logo_brown.svg">
			</a>
		</section>
	</nav>
</header>

<main style="background: #FBFBFB;" class="flowerDetail mainContainer">
    <section class="container-fluid fixed-width" style="background-color: #FFFCFE;">
        <section style="background-color: #FCF9F7;border: 0px;" class="flowerDescriptions row">
            <aside style="padding-left: 0px;" class="col-lg-offset-0 col-lg-3 col-md-offset-1 col-md-5 col-sm-offset-1 col-sm-5 col-xs-offset-0 col-xs-12">
                <div style="    position: absolute;z-index: 3;top: 50%;transform: translateY(-50%);" class="tagsContainer">
                    <ul>
                        @foreach($tagList as $tag)
                            <li>
                                <img class="tags active" src="{{$tag->inactive_image_url}}"/>
                                <!--<img class="tags active" src="{{$tag->active_image_url}}" ng-show="tag.isHovered"
                                     tooltip-placement="right" tooltip-html-unsafe="test"/>-->
                            </li>
                        @endforeach
                    </ul>
                </div>

                <figure class="flowerImageContainer col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <img style="width: 94.6%;" class="flowerImage" src="{{$flower->DetailImage}}" alt="{{$flower->img_title}}"/>
                </figure>
            </aside>
            <article class="col-lg-3 col-md-5 col-sm-5 col-xs-12">
                <header>
                    <h1 class="flowerName col-lg-12 col-md-12 col-sm-12 col-xs-11 text-center darkBlueText">
                        {{$flower->name}}
                    </h1>

                    <p class="col-lg-offset-0 col-lg-12 col-md-offset-1 col-md-10 col-sm-offset-1 col-sm-10 col-xs-offset-0 col-xs-12 flowerDescription text-center brownText">
                        {{$flower->detail_page_desc}}<br/>
                    </p>
                    <!--
                    <ul ng-if="flower.extra_info_1" class="extraInfo brownText text-center col-lg-offset-1 col-lg-11 col-md-offset-1 col-md-10 col-sm-offset-1 col-sm-10 col-xs-offset-0 col-xs-12">
                        <li>{{$flower->extra_info_1}}</li>
                        <li ng-if="flower.extra_info_2">{{$flower->extra_info_2}}</li>
                        <li ng-if="flower.extra_info_3">{{$flower->extra_info_3}}</li>
                    </ul>-->

                    <strong class="flowerPrice col-lg-11 col-md-11 col-sm-11 col-xs-11 darkBrownText text-center">
                        {{$flower->price}} TL
						<span class="flowerTax">
							+ KDV
						</span>
                    </strong>
                    <!--
                    <strong class="flowerPrice col-lg-11 col-md-11 col-sm-11 col-xs-11 text-center redText"
                            ng-if="flower.limit_statu !== '0' || flower.coming_soon !== '0' || !isActive">
						<span ng-if="flower.limit_statu === '1'">
						</span>
						<span ng-if="flower.coming_soon === '1'">
						</span>
						<span ng-if="!isActive">
						</span>
                    </strong>
                    -->
                </header>

                <footer>
                    <form class="col-lg-11 col-md-12 col-sm-11 col-xs-11 text-center ng-pristine ng-valid ng-scope" ng-if="isActive">
                        <label class="brownText not-error ng-binding" ng-hide="isChecked &amp;&amp; (flower.sendingDistrict === undefined || flower.sendingDistrict === null)">Gönderim Bölgesi</label>
                        <div id="bigDiv" onclick="setLocation();" class="hidden-xs ui-select-container select2 select2-container ng-valid ng-touched select2-container-active" theme="select2">
                            <a class="select2-choice ui-select-match select2-default" aria-label="Select box select" placeholder="Gönderim Bölgesi Yaz/Seç">
                                <span id="activeDistrict" class="select2-chosen ng-binding">Gönderim Bölgesi Yaz/Seç</span>
                                <span class="select2-chosen ng-hide" >
                                    <span class="ng-binding ng-scope"></span>
                                </span> <!-- ngIf: $select.allowClear && !$select.isEmpty() -->
                                <span class="select2-arrow ui-select-toggle">
                                    <b></b>
                                </span>
                            </a>
                            <div id="smallDiv" class="select2-drop select2-with-searchbox select2-display-none">
                                <div class="select2-search">
                                    <input type="search" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" role="textbox" aria-expanded="true" aria-owns="ui-select-choices-0" aria-label="Select box" aria-activedescendant="ui-select-choices-row-0-0" class="ui-select-search select2-input ng-pristine ng-valid ng-touched">
                                </div>
                                <ul role="tree" class="ui-select-choices ui-select-choices-content select2-results ng-scope" repeat="district in districts | filter: $select.search">
                                    <li role="group" class="ui-select-choices-group">
                                        <ul role="listbox" id="ui-select-choices-0" class="select2-result-single">
                                            <li onclick="setActiveDistrict('Beşiktaş-Levent')" role="treeitem" id="ui-select-choices-row-0-0" class="ui-select-choices-row ng-scope select2-highlighted" ng-repeat="district in $select.items">
                                                <div class="select2-result-label ui-select-choices-row-inner" uis-transclude-append="">
                                                    <span ng-bind-html="district.district | highlight: $select.search" class="ng-binding ng-scope">Beşiktaş-Levent</span>
                                                </div>
                                            </li>
                                            <li onclick="setActiveDistrict('Beşiktaş-Levent')"  role="treeitem" id="ui-select-choices-row-0-1" class="ui-select-choices-row ng-scope" ng-repeat="district in $select.items">
                                                <div class="select2-result-label ui-select-choices-row-inner" uis-transclude-append="">
                                                    <span ng-bind-html="district.district | highlight: $select.search" class="ng-binding ng-scope">Beşiktaş-Levent</span>
                                                </div>
                                            </li>
                                            <li onclick="setActiveDistrict('Besıktas-Levent')"  role="treeitem" id="ui-select-choices-row-0-2" class="ui-select-choices-row ng-scope" ng-repeat="district in $select.items">
                                                <div class="select2-result-label ui-select-choices-row-inner" uis-transclude-append="">
                                                    <span ng-bind-html="district.district | highlight: $select.search" class="ng-binding ng-scope">Besıktas-Levent</span>
                                                </div>
                                            </li>
                                            <li onclick="setActiveDistrict('Beyoglu-Cihangir')"  role="treeitem" id="ui-select-choices-row-0-3" class="ui-select-choices-row ng-scope" ng-repeat="district in $select.items">
                                                <div class="select2-result-label ui-select-choices-row-inner" uis-transclude-append="">
                                                    <span ng-bind-html="district.district | highlight: $select.search" class="ng-binding ng-scope">Beyoglu-Cihangir</span>
                                                </div>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                            <ui-select-single></ui-select-single>
                            <input class="ui-select-focusser ui-select-offscreen ng-scope" type="text" id="focusser-0" aria-label="Select box focus" aria-haspopup="true" role="button" disabled="">
                        </div>
                        <select class="mobilDropdownSelect hidden-lg hidden-md hidden-sm col-xs-12 ng-pristine ng-untouched ng-valid">
                            <option value="" class="ng-binding" selected="selected">Gönderim Bölgesi Yaz/Seç</option>
                            <option label="Beşiktaş-Levent" value="object:40">Beşiktaş-Levent</option>
                            <option label="Beşiktaş-Levent" value="object:41">Beşiktaş-Levent</option>
                            <option label="Besıktas-Levent" value="object:42">Besıktas-Levent</option>
                            <option label="Beyoglu-Cihangir" value="object:43">Beyoglu-Cihangir</option>
                        </select>
                        <a ng-click="send()" role="button" rel="nofollow" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 btn btn-default btn-bloomNfresh text-uppercase">
                            <b class="ng-binding">Gönder</b>
                        </a>
                    </form>
                    <section class="col-lg-12 col-md-12 col-sm-11 col-xs-12 shareSectionContainer">
                        <span class="shareText darkBrownText col-lg-offset-0 col-lg-9 col-md-offset-0 col-md-9 col-sm-offset-1 col-sm-6 col-xs-offset-0 col-xs-7">
                            Bu güzelliği paylaş
                        </span>

                        <ul class="shareButtonContainer col-lg-offset-0 col-lg-3 col-md-offset-0 col-md-3 col-sm-offset-0 col-sm-5 col-xs-offset-0 col-xs-5">
                            <li class="shareButtons col-lg-6 col-md-6 col-sm-6 col-xs-6"><a
                                    class="ion-social-facebook-outline" href="#"
                                    title="Bloom And Fresh Facebook Social Share"></a></li>
                            <li class="shareButtons col-lg-6 col-md-6 col-sm-6 col-xs-6"><a
                                    class="ion-social-twitter-outline" href="#"
                                    title="Bloom And Fresh Twitter Social Share"></a></li>
                        </ul>
                    </section>
                </footer>
            </article>
            <figure class="carouselContainer col-lg-6 col-md-12 col-sm-12 col-xs-6">
                <ul style="background-color: #FCF9F7;padding-left: 0px;" class="imagesCarousel center-block col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <li style="display: inline-block; transform: translate3d(0px, 0px, 0px);">
                        <img src="https://d1z5skrvc8vebc.cloudfront.net/188.166.86.116:3000/lovey-slide3.jpg" alt="Bloom And Fresh  Slide">
                    </li>
                </ul>
            </figure>
        </section>

        <article class="blog">
            <section style="background-color: white;" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <header class="blogHeader col-lg-3 col-md-6 col-sm-12 col-xs-12">
                    <h2  class="flowerBlogHeader lightBrownText"> {{$flower->how_to_title}} </h2>

                    <p>
                        {{$flower->how_to_detail}}
                    </p>
                </header>
                <aside class="firstAside col-lg-3 col-md-6 col-sm-12 col-xs-12">
                    <img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/icons/shape-1.png">

                    <p class="col-lg-11 col-md-11 col-sm-11 col-xs-12 brownText">
                        {{$flower->how_to_step1}}
                    </p>
                </aside>
                <aside class="secondAside col-lg-3 col-md-6 col-sm-12 col-xs-12">
                    <img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/icons/shape-1.png">

                    <p class="col-lg-11 col-md-11 col-sm-11 col-xs-12 brownText">
                        {{$flower->how_to_step2}}
                    </p>
                </aside>
                <aside class="secondAside col-lg-3 col-md-6 col-sm-12 col-xs-12">
                    <img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/icons/shape-1.png">

                    <p class="col-lg-11 col-md-11 col-sm-11 col-xs-12 brownText">
                        {{$flower->how_to_step3}}
                    </p>
                </aside>
            </section>
        </article>
    </section>

    <right-bottom-pop-up></right-bottom-pop-up>
</main>

<bf-footer></bf-footer>
<script>
    function setLocation(){
        if($('#bigDiv').hasClass('open')){
            $('#bigDiv').removeClass('open');
            $('#bigDiv').removeClass('select2-dropdown-open');
            $('#smallDiv').addClass('select2-display-none');
        }
        else{
            $('#smallDiv').removeClass('select2-display-none');
            $('#bigDiv').addClass('open');
            $('#bigDiv').addClass('select2-dropdown-open');
        }
    }

    $('.ui-select-choices-row').mouseenter(function(){
        console.log('qwerqwe');
        $(this).addClass('select2-highlighted');
    });

    $('.ui-select-choices-row').mouseleave(function(){
        console.log('1111');
        $(this).removeClass('select2-highlighted');
    });

    function setActiveDistrict(name){
        console.log('rqwerqwer');
        $('#activeDistrict').text(name);
    }
</script>
@stop