@extends('app2')
@section('content')
<div ng-hide="isRouteLoading" class="mainView ng-scope" ui-view="">
<main style="background-color: #FBFBFB" class="landingMain mainContainer" ng-app="landingModule">
    <header class="landingPage" style="height: inherit;min-height: 280px;min-width: 100px;z-index: 4;background-color: white;background-image: url(https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/banners/1_9.jpg)">
        <left-hidden-menu>
            <section class="overlay open" id="closeButtonId">
                <button type="button"  onclick="closeLeftMenu();"  class="closeButton btn btn-default btn-lg col-lg-offset-6 col-md-offset-6 col-sm-offset-11 col-xs-offset-10" aria-label="Left Align">
                    <span class="ion-android-close" aria-hidden="true"></span>
                </button>
                <nav class="overlayMenu panel col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <ul>
                        <li>
                            <img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/logos/bloomandfresh-logo3-v2.png" alt="Bloom And Fresh Logo">
                        </li>
                        <li>
                            <a class="center-block ng-scope" title="Bloom And Fresh Özel Butik Çiçek Siparişi" href="/testLanding">
                                <b class="text-uppercase darkBlueText mainPage ng-binding">Ana sayfa</b>
                            </a>
                        </li>
                        <li>
                            <a hreflang="tr" title="istanbul'un Stil Sahibi Online Cicekleri: Bloom and Fresh" href="/cicekler/bloomandfresh-istanbul-online-cicekleri">
                                <b class="darkBlueText text-uppercase flowers ng-binding">Çiçekler</b>
                            </a>
                        </li>
                        <li>
                            <a hreflang="tr" title="Bloom and Fresh - Ekibimiz Hakkında" href="/hakkimizda">
                                <b class="text-uppercase darkBlueText aboutUs ng-binding">Hakkımızda</b>
                            </a>
                        </li>
                        <li>
                            <a hreflang="tr" title="Bloom and Fresh bu güzel çiçekler nasıl hazırlanıyor?" href="/nasil-yapiyoruz/">
                                <b class="text-uppercase darkBlueText how ng-binding"> Nasıl Yapıyoruz</b>
                            </a>
                        </li>
                        <li>
                            <a hreflang="tr" title="Bloom and Fresh - Çiçeklere ve ekibimize ulaşın" href="/bize-ulasin">
                                <b class="text-uppercase darkBlueText contactUs ng-binding"> İletişim</b>
                            </a>
                        </li>
                        <li>
                            <a href="/kurumsal-siparisler" hreflang="tr" title="Bloom and Fresh - Çiçeklere ve ekibimize ulaşın">
                                <b class="text-uppercase darkBlueText contactUs"> Kurumsal Siparişler</b>
                            </a>
                        </li>
                        <li>
                            <a hreflang="tr" title="Bloom And Fresh - Soruların Cevapları" href="/destek">
                                <b class="text-uppercase darkBlueText help ng-binding"> Destek</b>
                            </a>
                        </li>
                        <li>
                            <a href="http://blog.bloomandfresh.com/" target="_blank" hreflang="tr" title="Bloom And Fresh - Blog">
                                <b class="text-uppercase darkBlueText blog ng-binding">Blog</b>
                            </a>
                        </li>
                    </ul>
                </nav>
            </section>
        </left-hidden-menu>
        <right-menus>
            @if (Auth::check())
            <section class="loginMenu signSection profile">
                <article class="container-fluid menuDisplay col-lg-6 col-md-10 col-sm-12 col-xs-12">
                    <button onclick="closeMenu();" type="button" class="closeButton btn btn-default btn-lg" aria-label="Left Align"></button>
                    <section class="panel text-center row">
                        <header class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <small class="lightBrownText col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center ng-binding">Merhaba</small>
                            <h3 class="text-uppercase col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <b class="ng-binding">qwe  rqwer</b>
                            </h3>
                            <button class="btn text-uppercase darkBlueText signOut">
                                <span class="icon ion-ios-close-empty"></span>
                                <span class="text ng-binding">Çıkış Yap</span>
                            </button>
                        </header>
                        <section class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <ul class="nav nav-pills">
                                <li role="presentation" class="tab-myBloom text-uppercase active">
                                    <a href="" class="">My Bloom</a>
                                </li>
                                <li role="presentation" class="tab-purchases text-uppercase col-lg-2 col-sm-3 col-xs-12">
                                    <a href="" class="ng-binding">Siparişlerin</a>
                                </li>
                                <li role="presentation" class="text-uppercase col-lg-2 col-md-3 col-sm-2 col-xs-12">
                                    <a href="" ng-click="setTab('kisilerin')" class="ng-binding">Kişilerin</a>
                                </li>
                                <li role="presentation" class="tab-userAccount text-uppercase col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                    <a href="" class="ng-binding">Hesap Bilgilerin</a>
                                </li>
                                <li role="presentation" class="tab-userAccount text-uppercase col-lg-2 col-md-3 col-sm-3 col-xs-12">
                                    <a href="" class="ng-binding">Hatırlatma</a>
                                </li>
                            </ul>
                            <section class="tabs">
                                <user-campaign ng-switch-when="myBloom" class="ng-scope">
                                    <section class="campaigns">
                                        <ul class="">
                                            <li ng-repeat="campaign in campaigns" class="campaignContainer ng-scope" href="/cicekler/bloomandfresh-istanbul-online-cicekleri">
                                                <figure class="campaignIconContainer">
                                                    <div class="iconBlock center-block">
                                                        <img class="campaignIcon" src="https://d1z5skrvc8vebc.cloudfront.net/coupons/10_indirim.png">
                                                    </div>
                                                </figure>
                                                <strong class="campaignName darkBlueText ng-binding"> 10% Tanışma İndirimi </strong>
                                                <p class="campaignInfo center-block text-center darkOrangeText ng-binding"> Yeni üye olanlara verilen tek kullanımlık indirim. </p>
                                                <a role="button" class="useCampaign" href="/cicekler/bloomandfresh-istanbul-online-cicekleri">
                                                    <span class="ng-binding">KULLAN</span>
                                                </a>
                                            </li>
                                            <li class="campaignContainer addCampaign ng-scope">
                                                <section class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                    <button type="button" class="btn-add btn btn-default btn-lg" aria-label="Left Align">
                                                        <span class="middleBrownText glyphicon glyphicon-plus" aria-hidden="true"></span>
                                                    </button>
                                                </section>
                                                <span class="addCardText center-block lightBrownText col-lg-8 ng-binding"> İndirim Kuponu Ekle </span>
                                            </li>
                                        </ul>
                                    </section>
                                </user-campaign>
                            </section>
                        </section>
                    </section>
                </article>
            </section>
            @else
            <section class="loginMenu signSection signIn">
                            <form role="form" method="POST" action="/testLogin" class="container-fluid signForm menuDisplay signInForm col-lg-6 col-md-10 col-sm-12 col-xs-12 ng-scope ng-valid-email ng-valid-pattern ng-valid-maxlength ng-valid-minlength ng-dirty ng-valid-parse ng-valid ng-valid-required"
                            name="signInForm">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button onclick="closeMenu();" type="button" class="closeButton btn btn-default btn-lg"></button>
                                <fieldset class="panel text-center">
                                    <h3 class="text-uppercase darkBlueText col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <b class="ng-binding">
                                            Giriş Yap
                                        </b>
                                    </h3>
                                    <div class="inputContainer center-block">
                                        <span class="input input--akira input--filled">
                                            <input class="lightBrownText input__field input__field--akira ng-valid-email ng-valid-pattern ng-valid-maxlength ng-dirty ng-valid ng-valid-required ng-touched" type="email" id="signInUserMail" name="email" maxlength="60" required="">
                                            <label class="input__label input__label--akira" for="signInUserMail">
                                                <span class="input__label-content input__label-content--akira">
                                                    <span class="error ng-binding hidden">
                                                        E-posta Adresinde Bir Sorun Var
                                                    </span>
                                                    <span class="error ng-binding hidden">
                                                        E-posta Adresini Boş Bıraktın
                                                    </span>
                                                    <span class="lightBrownText not-error ng-binding">
                                                        E-posta adresin
                                                    </span>
                                                </span>
                                            </label>
                                        </span>
                                    </div>
                                    <div class="inputContainer center-block">
                                        <span class="input input--akira input--filled">
                                            <input class="lightBrownText input__field input__field--akira ng-untouched ng-valid-minlength ng-valid-maxlength ng-dirty ng-valid-parse ng-valid ng-valid-required" type="password" id="signInUserPassword" name="password"  maxlength="30" required="">
                                            <label class="input__label input__label--akira" for="signInUserMail">
                                                <span class="input__label-content input__label-content--akira">
                                                    <span class="error ng-binding hidden">Şifrende Bir Sorun Var</span>
                                                    <span class="error ng-binding hidden">Şifreni Boş Bıraktın</span>
                                                    <span class="lightBrownText not-error ng-binding">Şifren</span>
                                                </span>
                                            </label>
                                        </span>
                                    </div>
                                    <section class="button">
                                        <button type="submit" onclick="$('#submitType').val('login');" class="btn-bloomNfresh btn btn-default text-uppercase ng-binding ng-scope">
                                            Giriş Yap
                                        </button>
                                        <a role="button" class="forgetPassword lightBrownText ng-binding">Şifremi Unuttum</a>
                                    </section>
                                    <footer class="signUp" style="padding-top: 0px;">
                                        <section class="buttons ng-scope" style="padding-left: 23px;">
                                            <button type="submit" onclick="$('#submitType').val('register');" class="btn text-uppercase darkBlueText signUp center-block">
                                                <b class="ng-binding">Üye Ol</b>
                                            </button>
                                            <span class="lightBrownText center-block ng-binding">veya</span>
                                            <button type="submit" onclick="$('#submitType').val('FB');" class="btn darkBlueText center-block fbButton text-uppercase ng-scope">
                                                <b class="ng-binding">FACEBOOK İLE BAĞLAN</b>
                                            </button>
                                            </section><!-- end ngIf: !toPurchase --> <!-- ngIf: toPurchase -->
                                    </footer>
                                </fieldset>
                                <input id="submitType" name="submitType" class="hidden">
                            </form>
                        </section>
            @endif
        </right-menus>

        <nav class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="row">
                <button onclick="openLeftMenu()" type="button" class="secretMenu btn btn-default btn-lg pull-left" aria-label="Left Align" >
                    <span class="glyphicon ion-navicon darkBlueText" aria-hidden="true"></span>
                </button>
                @if (Auth::check())
                    <button onclick="openMenu();" type="button" class="signIn btn btn-default btn-lg pull-right" aria-label="Right Align">
                        <span class="glyphicon userNameFirstChar text-uppercase darkBlueText" aria-hidden="true">
                            <b class="darkBlueText ng-binding">h</b>
                        </span>
                    </button>
                @else
                    <button onclick="openMenu();" type="button" class="signIn btn btn-default btn-lg pull-right" aria-label="Left Align">
                        <span class="signInBlueIcon signInIcon darkBlueText" aria-hidden="true"></span>
                    </button>
                @endif
                <figure class="icon center-block">
                    <img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/logos/bloomandfresh-logo-v2.svg" class="">
                </figure>
            </div>
        </nav>

        <div style="width: 100%;padding-left: 0px;padding-right: 0px;padding-top: 0px" class="container">
            <div id="myCarousel" class="carousel slide" data-ride="carousel">
                <!-- Indicators -->
                <ol class="carousel-indicators">
                    @foreach( $bannerList as $key=>$banner )
                        @if( $key == 0)
                            <li data-target="#myCarousel" data-slide-to="{{$key}}" class="active"></li>
                        @else
                            <li data-target="#myCarousel" data-slide-to="{{$key}}"></li>
                        @endif
                    @endforeach
                </ol>

                <!-- Wrapper for slides -->
                <div class="carousel-inner" role="listbox">
                    @foreach( $bannerList as $key=>$banner )
                        <div style="    height: 560px;
                                        background: center no-repeat;
                                        background-size: cover;
                                        background-image: url('{{$banner->img_url}}')" class="item
                        @if( $key == 0)
                        active
                        @else
                        @endif">
                            <a href="{{$banner->url}}">
                                <section class="container headerText">
                                    <h1 class="promoText text-center darkBlueText center-block ng-binding" style="text-shadow: 0 0 14px {{$banner->background_color}} ;color: {{$banner->font_color}};">
                                        @foreach( explode( '<br>' , $banner->header) as $bannerText )
                                            {{$bannerText}}
                                            <br>
                                        @endforeach
                                    </h1>
                                </section>
                            </a>
                        </div>
                    @endforeach
                    <!--
                    <div class="item">
                        <img src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/188.166.86.116/18_1.jpg" alt="Chania" width="460" height="345">
                    </div>

                    <div class="item">
                        <img src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/188.166.86.116/18_1.jpg" alt="Flower" width="460" height="345">
                    </div>

                    <div class="item">
                        <img src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/188.166.86.116/18_1.jpg" alt="Flower" width="460" height="345">
                    </div>-->
                </div>

                <!-- Left and right controls -->
                <a class="left carousel-control" href="#myCarousel" role="button" data-slide="prev">
                    <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="right carousel-control" href="#myCarousel" role="button" data-slide="next">
                    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>
            </div>
        </div>
    </header>
    <!--
    <header class="landingPage" style="min-height: 280px;min-width: 100px;z-index: 4;background-color: white;background-image: url(https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/banners/1_9.jpg)">
        <left-hidden-menu></left-hidden-menu>
        <right-menus></right-menus>

        <nav class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="row">
                <button type="button" class="signIn btn btn-default btn-lg pull-right"
                        aria-label="Left Align">
                    <span class="signInBlueIcon signInIcon darkBlueText" aria-hidden="true"></span>
                </button>
                <figure class="icon center-block">
                    <img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/logos/bloomandfresh-logo-v2.svg" class="">
                </figure>
            </div>
        </nav>

        <carousel style="z-index: 4;background-color: white;" interval="5000" class="hidden-xs">
        @foreach($bannerList as $banner)
            <slide>
                <a href="{{$banner->url}}" title="Bloom And Fresh">
                    <section class="container headerText">
                        <h1 class="promoText text-center darkBlueText center-block"></h1>
                    </section>
                    <div class="img-container" style="{ 'background-image': 'url({{$banner->img_url}})' }"></div>
                </a>
            </slide>
        @endforeach
        </carousel>

        <a style="min-width: 100px;min-height:280px" href=""
           title="Bloom And Fresh" class="hidden-lg hidden-md hidden-sm mobilHeader">
            <section class="container headerText">
                <h1 class="promoText text-center darkBlueText center-block"> </h1>
            </section>
        </a>
    </header>-->

    <section class="flowersSection container-fluid fixed-width" id="cicekler">
        <section class="row flowers">
            @foreach($flowerList as $flower)
                <a style="z-index: 4;" class="flowerContainer col-lg-3 col-md-3 col-sm-6 col-xs-12" href="/flowerDetail/{{$flower->id}}">
                   <!-- ng-style="{'background-color': flower.background_color}"      > -->
                    <h4 style="position: absolute;z-index: 2" class="flowerName col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center darkBlueText"><span
                            class="textElement">{{$flower->name}}</span></h4>

                    <p style="position: absolute;z-index: 2;margin-top: 60px;" class="flowerDescription col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center lightOrangeText">
                        {{$flower->landing_page_desc}}</p>

                    <figure style="min-width: 200px;min-height: 300px;" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 flowerImage">
                            <!--ng-style="{'background-color': flower.second_background_color}">-->
                        <img src="{{$flower->MainImage}}" class="flowerImg center-block">
                        <figcaption style="position: absolute;z-index: 2;"  class="flowerPrice col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center darkBlueText">
                            <span> {{$flower->price}} TL </span>
                            <span class="flowerTax"> + KDV</span>
                        </figcaption>
                        <button style="z-index: 2;"  class="whiteText btn btn-default col-lg-6 col-md-6 col-sm-6 col-xs-6 center-block sendFlowerButton">
                            <b>Gönder</b>
                        </button>
                    </figure>
                </a>
            @endforeach
        </section>
    </section>

    <section style="background-color: #FBFBFB;" class="infoSection">
        <section class="container-fluid fixed-width" id="nereyeGonderiyoruz">
            <section style="padding-bottom: 3.7em;" class="row social">
                <div class="form-inline col-lg-offset-0 col-lg-6 col-md-offset-0 col-md-6 col-sm-offset-1 col-sm-10 col-xs-offset-0 col-xs-12"
                      >
                    <aside style="padding-left: 0px;"  class="col-lg-offset-1 col-lg-6 col-md-7 col-sm-6 col-xs-12">
                        <h4 style="padding-left: 0px;" class="infoSectionHeader darkBlueText col-lg-12 col-md-12 col-sm-12 col-xs-12 text-left">
                            HABERİN OLSUN</h4>

                        <p style="padding-left: 0px;" class="explanation lightBrownText text-justify col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            Yeni bir tasarım yaptığımızda, yeni taptaze bir çiçek bulduğumuzda, yeni bir gönderim bölgesi eklediğimizde veya senin de seveceğin bir şeyler bulduğumuzda haberin olsun!</p>
                    </aside>
                    <bf-news-subscription class="form-group col-lg-5 col-md-5 col-sm-offset-0 col-sm-5 col-xs-offset-1 col-xs-10">
                        <fieldset>
                            <label class="error serverError ng-binding"></label>
                            <input type="email" class="form-control ng-pristine ng-valid ng-valid-email ng-valid-maxlength ng-touched" id="email" name="subscriptionMail" placeholder="E-posta adresin" maxlength="60" autocomplete="off">
                                <button onclick="sendNewsLetter();">
                                    <span class="glyphicon glyphicon-arrow-right ng-scope"></span><!-- end ngIf: !IsSuccess --> <!-- ngIf: IsSuccess -->
                                </button>
                        </fieldset>
                    </bf-news-subscription>
                </div>
                <aside class="socialAccounts col-lg-offset-0 col-lg-6 col-md-offset-0 col-md-6 col-sm-offset-1 col-sm-10 col-xs-offset-0 col-xs-12">
                    <section style="padding-left: 0px;" class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <h4 style="padding-left: 0px;" class="infoSectionHeader col-lg-12 col-md-12 col-sm-12 col-xs-12 darkBlueText text-left">
                            TAKİPLEŞELİM</h4>

                        <p style="padding-left: 0px;" style="width: 81%;" class="explanation lightBrownText text-justify col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            Hayat sence de Instagram filtreleriyle daha güzel değil mi?</p>
                    </section>

                    <a href="https://instagram.com/bloomandfresh/" target="_blank"
                       class="bottom-xs col-lg-1 col-md-1 col-sm-1 col-xs-3 instagramButton"><img
                            src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/icons/social-icons/instagram-icon-red.png"
                            ></a>
                    <a href="https://www.facebook.com/bloomandfresh?ref=hl" target="_blank"

                       class="col-lg-1 col-md-1 col-sm-1 col-xs-3 facebookButton"><img
                            src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/icons/social-icons/facebook-icon-red.png"></a>
                    <a href="https://www.pinterest.com/bloomandfresh/" target="_blank"

                       class="col-lg-1 col-md-1 col-sm-1 col-xs-3 pinterestButton"><img
                            src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/icons/social-icons/pinterest-icon-red.png"
                            ></a>
                    <a href="https://twitter.com/BloomAndFresh" target="_blank"

                       class="bottom-xs col-lg-1 col-md-1 col-sm-1 col-xs-3 twitterButton"><img
                            src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/icons/social-icons/twitter-icon-red.png"></a>
                </aside>
            </section>
            <section style="padding-top: 0px;" class="row how">
                <article  style="padding-left: 0px;"
                        class="col-lg-offset-0 col-lg-4 col-md-offset-0 col-md-4 col-sm-offset-1 col-sm-9 col-xs-offset-0 col-xs-12">
                    <aside  style="padding-top: 6px;" class="howDescription col-lg-offset-1 col-lg-11 col-md-12 col-sm-12 col-xs-12">
                        <h4 style="padding-left: 0px;font-size: 30px;line-height: 30px;" class="infoSectionHeader darkBlueText col-lg-12 col-md-12 col-sm-12 col-xs-12 text-uppercase text-left">
                            BLOOM AND FRESH SIRLARI</h4>

                        <p style="padding-left: 0px;" class="explanation darkBlueText  text-justify col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            Farklı olduğunu düşünüyorsan gönderdiğin çiçeklerin de farklı olması gerekmez mi? Seni ve bizi farklı kılan Bloom and Fresh sırlarını açıklıyoruz.</p>

                        <p style="padding-left: 0px;" class="explanation lightBrownText  text-justify col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            B&F düzenlemelerini tasarım, detaylara özen ve tazelik etrafında oluşturur, en taze çiçekleri ve estetik düzenlemeleri sunabilmek için sınırlı koleksiyon anlayışıyla çalışır. Sana veya seçtiğin şanslı kişiye gönderilen çiçekler özenle hazırlanır ve fotoğraflarında gördüğün tasarım ve içeriklere sadık kalınarak oluşturulur. Son olarak da bu güzel çiçekleri ve düzenlemeleri en hızlı şekilde gönderebilmeniz için en kullanışlı arayüzü ve uygun teknolojileri kullanır. Gönderilerinizi en hızlı şekilde teslim eder.</p>

                        <section class="howSteps hidden-lg hidden-md hidden-sm col-xs-12">
                            <ul>
                                <li class="col-xs-6">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-tasarim-sirlari-2.svg"
                                             alt="İstanbul'un en iyi çiçek tasarımları">
                                        <figcaption class="col-lg-11 text-center text-uppercase">
                                           Tasarım
                                        </figcaption>
                                    </figure>
                                </li>
                                <li class="col-xs-6">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-detay-sirlari-2.svg"
                                             alt="Her detayı düşünülmüş çiçek gönderim hizmeti">
                                        <figcaption class="col-lg-11 text-center text-uppercase">
                                            Detay
                                        </figcaption>
                                    </figure>
                                </li>
                            </ul>
                        </section>

                        <p  style="padding-left: 0px;" class="explanation lightBrownText  text-justify col-lg-12 col-md-12 col-sm-12 col-xs-12"></p>

                        <section class="howSteps hidden-lg hidden-md hidden-sm col-xs-12">
                            <ul>
                                <li class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-tazelik-sirlari-2.svg"
                                             alt="İstanbul'un en taze çiçeklerini gönder">
                                        <figcaption class="col-lg-11 text-center text-uppercase">
                                            Tazelik
                                        </figcaption>
                                    </figure>
                                </li>
                                <li class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-koleksiyon-sirlari-2.svg"
                                             alt="Özel koleksiyon çiçeklerimiz">
                                        <figcaption class="col-lg-12 text-center text-uppercase">
                                            Sınırlı Koleksiyon
                                        </figcaption>
                                    </figure>
                                </li>
                            </ul>
                        </section>
                        <p  style="padding-left: 0px;" class="explanation lightBrownText  text-justify col-lg-12 col-md-12 col-sm-12 col-xs-12"></p>

                        <section  style="width: 101%;" class="howSteps hidden-lg hidden-md hidden-sm col-xs-12">
                            <ul>
                                <li class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-goruntu-sirlari-2.svg"
                                             alt="Ekranda gördüğünün aynı şekilde giden çiçekler">
                                        <figcaption class="col-lg-12 text-center text-uppercase">
                                            Göründüğü Gibi
                                        </figcaption>
                                    </figure>
                                </li>
                                <li class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-teslimat-sirlari-2.svg"
                                             alt="Hızlı çiçek siparişi ve teslimatı">
                                        <figcaption class="fastDelivery col-lg-12 text-center text-uppercase">
                                            Hızlı Sipariş ve Teslimat
                                        </figcaption>
                                    </figure>
                                </li>
                            </ul>
                        </section>
                        <a style="float: left !important;" class="text-uppercase text-right lightBrownText pull-right"><b>DAHA FAZLA DETAY</b> </a>
                    </aside>
                </article>
                <section class="howSteps table-responsive col-lg-8 col-md-8 col-sm-12 hidden-xs">
                    <table style="width: 96%" class="table .table-bordered">
                        <tr>
                            <td style="border-top: 0px;" class="col-lg-4">
                                <a class="stepLink">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-tasarim-sirlari-2.svg">
                                        <figcaption class="col-lg-11 text-center text-uppercase">
                                            tASARIM
                                        </figcaption>
                                    </figure>
                                </a>
                            </td>
                            <td style="border-top: 0px;" class="col-lg-4">
                                <a class="stepLink">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-detay-sirlari-2.svg">
                                        <figcaption class="col-lg-11 text-center text-uppercase">
                                            DETAYLAR
                                        </figcaption>
                                    </figure>
                                </a>
                            </td>
                            <td style="border-top: 0px;" class="col-lg-4">
                                <a class="stepLink">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-tazelik-sirlari-2.svg">
                                        <figcaption class="col-lg-11 text-center text-uppercase">
                                            TAZELİK
                                        </figcaption>
                                    </figure>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-top: 0px;" class="col-lg-4">
                                <a class="stepLink">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-koleksiyon-sirlari-2.svg">
                                        <figcaption class="col-lg-12 text-center text-uppercase">
                                            SINIRLI KOLEKSİYON
                                        </figcaption>
                                    </figure>
                                </a>
                            </td>
                            <td style="border-top: 0px;" class="col-lg-4">
                                <a class="stepLink">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-goruntu-sirlari-2.svg">
                                        <figcaption class="col-lg-12 text-center text-uppercase">
                                            GÖRÜNDÜĞÜ GİBİ
                                        </figcaption>
                                    </figure>
                                </a>
                            </td>
                            <td style="border-top: 0px;" class="col-lg-4">
                                <a class="stepLink">
                                    <figure class="stepContainer">
                                        <img class="stepImg center-block"
                                             src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/howImages/bloomandfresh-teslimat-sirlari-2.svg">
                                        <figcaption class="fastDelivery col-lg-12 text-center text-uppercase">
                                            HIZLI SİPARİŞ VE TESLİMAT
                                        </figcaption>
                                    </figure>
                                </a>
                            </td>
                        </tr>
                    </table>
                </section>
            </section>
            <section class="row districts">
                <aside style="margin-top: 0px;" class="districtMap col-lg-7 col-md-7 hidden-sm hidden-xs">
                    <img style="margin-left: 5%;"  src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/promos/bf-cicek-haritasi.png">
                </aside>
                <article style="margin-top: 0px;padding-right: 0px;"
                        class="ourDistricts col-lg-offset-0 col-lg-5 col-md-offset-0 col-md-5 col-sm-offset-1 col-sm-10 col-xs-offset-0 col-xs-12">
                    <section style="margin-left: 0px;padding-right: 0px;padding-left: 0px;" class="info col-lg-offset-1 col-lg-11 col-md-12 col-sm-12 col-xs-12">
                        <h4 style="text-align: right;font-size: 30px;line-height: 30px;"  class="col-lg-12 col-md-12 col-sm-12 darkBlueText text-uppercase text-left">NEREYE GÖNDERİYORUZ?</h4>

                        <p style="text-align: right;float: right" class="col-lg-12 col-md-12 col-sm-10 col-xs-12 lightBrownText">B&F kalitesini ve çiçeklerinin tazeliğini korumak için operasyonunu kontrollü olarak büyütüyor. İstanbul’da şimdilik gönderim yaptığımız ilçeleri aşağıda görebilirsin. Gönderim alanlarımızı genişletmek için var gücümüzle çalıştığımızı bilmeni isteriz.</p>

                        <div class="district-names col-lg-4 col-md-4 col-sm-4 col-xs-6">
                            <h5 class="darkBlueText" title="Bloom And Fresh ataşehir">ATAŞEHİR*</h5>

                            <h5 class="darkBlueText" title="Bloom And Fresh beşiktaş">BEŞİKTAŞ</h5>

                            <h5 class=" darkBlueText" title="Bloom And Fresh beykoz">BEYKOZ*</h5>

                            <h5 class="darkBlueText" title="Bloom And Fresh beyoğlu">BEYOĞLU*</h5>
                        </div>
                        <div class="district-names col-lg-4 col-md-4 col-sm-4 col-xs-6">
                            <h5 class="darkBlueText" title="Bloom And Fresh eyüp">EYÜP*</h5>

                            <h5 class="darkBlueText" title="Bloom And Fresh fatih">FATİH</h5>

                            <h5 class="darkBlueText" title="Bloom And Fresh kadiköy">KADIKÖY*</h5>

                            <h5 class="darkBlueText" title="Bloom And Fresh kağıthane">KAĞITHANE*</h5>


                        </div>
                        <div class="district-names col-lg-4 col-md-4 col-sm-4 col-xs-12">
                            <h5 class=" text-uppercase darkBlueText" title="Bloom And Fresh sariyer">SARIYER*</h5>

                            <h5 class="darkBlueText" title="Bloom And Fresh sisli">ŞİŞLİ*</h5>

                            <h5 class="darkBlueText" title="Bloom And Fresh ümraniye">ÜMRANİYE*</h5>

                            <h5 class="darkBlueText" title="Bloom And Fresh üsküdar">ÜSKÜDAR*</h5>
                        </div>

                        <p class="darkBlueText col-lg-12 col-md-12 col-sm-10 col-xs-12" style="font-size: 13px;text-align: right;">
                            * Şimdilik belirli semt ve mahallelere gönderim yapabiliyoruz.
                        </p>
                    </section>
                </article>
            </section>
        </section>
    </section>

    <article class="bloomBlog">
        <section class="container-fluid fixed-width">
            <header style="padding-bottom: 3em;background-color: white;"  class="row">
                <aside class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                    <section style="padding-top: 20px;padding-left: 9%;"
                            class="col-lg-offset-1 col-lg-11 col-md-11 col-sm-offset-1 col-sm-9 col-xs-offset-0 col-xs-12">
                        <h4 class=" text-left lightBrownText">ÜÇ ADIMDA STİL SAHİBİ ÇİÇEKLER SENİN İÇİN HAZIR</h4>
                        <p class="darkBlueText">Bu güzel çiçeklere sahip olmak veya en az onlar kadar güzel ve şanslı birilerine göndermek istersen yapman gerekenler çok basit:</p>
                    </section>
                </aside>
                <article class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                    <figure  style="margin-left: 5%;"  class="col-lg-offset-1 col-lg-3 col-md-offset-1 col-md-3 col-sm-offset-1 col-sm-3 col-xs-offset-0 col-xs-12">
                        <img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/bloomBlog/bloomandfresh-blog-cicegini-sec-2.png"
                             class="center-block howImg-1">
                        <figcaption class="text-center darkBlueText">ÇİÇEĞİNİ SEÇ
                        </figcaption>
                        <p class="text-center lightBrownText"> Özel tasarlanmış ve şanslı kişiyi gülümsetme garantili çiçeklerimizden istediğini seç ve hemen siparişini ver. Kararsız kalmak yok, inan hepsi çok özel.</p>
                    </figure>
                    <figure  style="margin-left: 8%;" class="col-lg-offset-1 col-lg-3 col-md-3 col-sm-offset-1 col-sm-3 col-xs-offset-0 col-xs-12 howWeDoItFigure">
                        <img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/bloomBlog/bloomandfresh-blog-bize-birak-2.png"
                             class="center-block howImg-2">
                        <figcaption class="text-center darkBlueText">BİZE BIRAK
                        </figcaption>
                        <p class="text-center lightBrownText"> B&F ekibine kısa bir süre ver. Çiçeğini en güzel şekilde kendi elleriyle hazırlasınlar ve şanslı kişiye doğru yola koyulsunlar.</p>
                    </figure>
                    <figure style="margin-left: 7%;" class="col-lg-offset-1 col-lg-3 col-md-3 col-sm-offset-1 col-sm-3 col-xs-offset-0 col-xs-12 howWeDoItFigure">
                        <img src="https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/bloomBlog/bloomandfresh-blog-gulumseme-garantili-2.png"
                             class="center-block howImg-3">
                        <figcaption class="text-center darkBlueText">GÜLÜMSEME GARANTİLİ
                        </figcaption>
                        <p class="text-center lightBrownText"> Çiçeğin teslim edildiğinde B&F haber verecektir ama çiçeklerimizi gören şanslı kişinin üstün B&F teknolojisinden bile hızlı davranıp sana çoktan haber vermiş olması daha olası.</p>
                    </figure>
                </article>
            </header>
        </section>
    </article>

    <article class="bloomBlog">
        <section class="container-fluid fixed-width">
            <header class="row" style="border: 0px;padding-top: 0px;padding-bottom: 0px;">
                <aside class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <section
                            class="col-lg-offset-1 col-lg-11 col-md-11 col-sm-offset-1 col-sm-9 col-xs-offset-0 col-xs-12">
                        <br>
                        <br>
                        <h4 style="font-size: 18px;color: #3b454d;font-size: 30px;line-height: 30px;" class=" text-left lightBrownText">KURUMSAL SİPARİŞ Mİ?</h4>
                        <p class="darkBlueText">Kurumunuz adına özel veya toplu gönderileriniz mi var?</p>
                        <br>
                        <p style="font-size: 16px;font-family: 'Gotham Light';line-height: 25px;margin-bottom: 2.25em;padding-right: 28%;color: #8D7758;">
                            Tasarımlarımızı çok beğendiniz ve kurumunuz adına toplu veya özel gönderim mi yapmak istiyorsunuz? Sizi böyle alalım!<a href="/kurumsal-siparisler"><img style="    padding-left: 10px;padding-top: 4px;" src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/bloomandfresh.com/images/landingOk.png"></a>
                        </p>
                    </section>
                </aside>
                <article style="padding-right: 0px;padding-left: 0px;" class="hidden-xs hidden-sm col-lg-6 col-md-6">
                    <img src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/bloomandfresh.com/images/landingCompany.png" style="position: absolute;padding-top: 23%;margin-left: -24px;">
                    <img style="width: 100%;" src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/bloomandfresh.com/kurumImage.jpg">
                </article>
            </header>
        </section>
    </article>

    <right-bottom-pop-up></right-bottom-pop-up>
</main>
</div>
<bf-footer></bf-footer>
<script>
    function openMenu(){
        console.log('qwerqwere');
        $('.loginMenu').addClass("open");
    }

    function sendNewsLetter(){
        return false;
    }

    function closeMenu(){
        $('.loginMenu').removeClass("open");
    }

    function openLeftMenu(){
        $('#closeButtonId').addClass('open');
    }

    function closeLeftMenu(){
        $('#closeButtonId').removeClass('open');
    }
</script>
@stop