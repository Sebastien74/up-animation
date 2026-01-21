JUNI : 
    - OPTIMISER le controller image loader
    - OPTIMISER le htaccess

#[ORM\JoinColumn Dépreciation
#[ORM\JoinColumn Dépreciation
#[ORM\JoinColumn Dépreciation

        {{ dump('récupérer user front back compte de IBW') }}

{#{{ dump('Mettre routeArgs dans FormHelper') }}#}
{#{{ dump('_glightbox comme My roller derby avec facebook pour info match') }}#}
{#{{ dump('Faire Teaser et index event comme MRD') }}#}

{#{{ dump('Désativer le wifi pour les font avant chargement') }}#}
{#{{ dump('Désativer le wifi pour les font avant chargement') }}#}
{#{{ dump('Désativer le wifi pour les font avant chargement') }}#}
{#{{ dump('Désativer le wifi pour les font avant chargement') }}#}
{#{{ dump('Désativer le wifi pour les font avant chargement') }}#}
{#{{ dump('Désativer le wifi pour les font avant chargement') }}#}
{#{{ dump('Désativer le wifi pour les font avant chargement') }}#}

{#{{ dump('Vérifier crop back en rétina') }}#}
{#{{ dump('Vérifier crop back en rétina') }}#}
{#{{ dump('Vérifier crop back en rétina') }}#}
{#{{ dump('Vérifier crop back en rétina') }}#}
{#{{ dump('Vérifier crop back en rétina') }}#}
{#{{ dump('Vérifier crop back en rétina') }}#}
{#{{ dump('Vérifier crop back en rétina') }}#}
{#{{ dump('Vérifier crop back en rétina') }}#}
{#{{ dump('Vérifier crop back en rétina') }}#}
{#{{ dump('Vérifier crop back en rétina') }}#}

{#{{ dump('Actualits trop long la premiere fois si pas images thumbs générées') }}#}
{#{{ dump('Boutons lazy load et asych') }}#}
{#{{ dump('Upload max file size serveur dans les form') }}#}
{#{{ dump('Déplacer les modules bootrap JS dans default et apppeler les css associé dedans') }}#}
{#{{ dump('Déplacer les modules bootrap JS dans default et apppeler les css associé dedans') }}#}
{#{{ dump('Déplacer les modules bootrap JS dans default et apppeler les css associé dedans') }}#}
{#{{ dump('Déplacer les modules bootrap JS dans default et apppeler les css associé dedans') }}#}
{#{{ dump('Déplacer les modules bootrap JS dans default et apppeler les css associé dedans') }}#}
{#{{ dump('Faire les media query directement dans bootstrap cad déjà séparer les mobile et les desktop') }}#}
{#{{ dump('Varibale bootstrap first-paint') }}#}
{#{{ dump('Varibale bootstrap mobile') }}#}
{#{{ dump('Varibale bootstrap desktop') }}#}
{#{{ dump('Faire bootstrap list scss') }}#}
{#{{ dump('Charger bootstrap en premier') }}#}
{#{{ dump('Charger app template home/cms... en deuxième') }}#}
{#{{ dump('Charger app responsive home/cms... en troisième') }}#}
{#{{ dump('') }}{{ dump('Faire les media query directement dans bootstrap cad déjà séparer les mobile et les desktop') }}#}
{#{{ dump('Varibale bootstrap first-paint') }}#}
{#{{ dump('Varibale bootstrap mobile') }}#}
{#{{ dump('Varibale bootstrap desktop') }}#}
{#{{ dump('Faire bootstrap list scss') }}#}
{#{{ dump('Charger bootstrap en premier') }}#}
{#{{ dump('Charger app template home/cms... en deuxième') }}#}
{#{{ dump('Charger app responsive home/cms... en troisième') }}#}
{#{{ dump('') }}{{ dump('Faire les media query directement dans bootstrap cad déjà séparer les mobile et les desktop') }}#}
{#{{ dump('Varibale bootstrap first-paint') }}#}
{#{{ dump('Varibale bootstrap mobile') }}#}
{#{{ dump('Varibale bootstrap desktop') }}#}
{#{{ dump('Faire bootstrap list scss') }}#}
{#{{ dump('Charger bootstrap en premier') }}#}
{#{{ dump('Charger app template home/cms... en deuxième') }}#}
{#{{ dump('Charger app responsive home/cms... en troisième') }}#}
{#{{ dump('') }}{{ dump('Faire les media query directement dans bootstrap cad déjà séparer les mobile et les desktop') }}#}
{#{{ dump('Varibale bootstrap first-paint') }}#}
{#{{ dump('Varibale bootstrap mobile') }}#}
{#{{ dump('Varibale bootstrap desktop') }}#}
{#{{ dump('Faire bootstrap list scss') }}#}
{#{{ dump('Charger bootstrap en premier') }}#}
{#{{ dump('Charger app template home/cms... en deuxième') }}#}
{#{{ dump('Charger app responsive home/cms... en troisième') }}#}
{#{{ dump('') }}#}


onclick="tinyMCE.triggerSave(true,true);" A METTRE SUR BTN SUBMIT SI EDITOR modal zone config par exemple si bod
onclick="tinyMCE.triggerSave(true,true);" A METTRE SUR BTN SUBMIT SI EDITOR modal zone config par exemple si bod
onclick="tinyMCE.triggerSave(true,true);" A METTRE SUR BTN SUBMIT SI EDITOR modal zone config par exemple si bod
onclick="tinyMCE.triggerSave(true,true);" A METTRE SUR BTN SUBMIT SI EDITOR modal zone config par exemple si bod
onclick="tinyMCE.triggerSave(true,true);" A METTRE SUR BTN SUBMIT SI EDITOR modal zone config par exemple si bod
onclick="tinyMCE.triggerSave(true,true);" A METTRE SUR BTN SUBMIT SI EDITOR modal zone config par exemple si bod
onclick="tinyMCE.triggerSave(true,true);" A METTRE SUR BTN SUBMIT SI EDITOR modal zone config par exemple si bod

remplace les for loop JS front par foreach

Revoir Remove Thumbnails et voir pour virer les query dans MediaRelationRepository

&nbsp;! AUto dans vue

#[Assert\Valid(['groups' => ['form_submission']])]

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType {
public function buildForm(FormBuilderInterface $builder, array $options) {
$builder
->add('username')
->add('profile', ProfileType::class);
}

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default', 'form_submission'],
        ]);
    }
}

SHARE START

import ClipboardJS from 'clipboard'
import { createPopper } from '@popperjs/core';

async function AndroidNativeShare(Title, URL, Description) {
if (typeof navigator.share === 'undefined' || !navigator.share) {
alert('Your browser does not support Android Native Share, it\'s tested on chrome 63+');
} else if (window.location.protocol != 'https:') {
alert('Android Native Share support only on Https:// protocol');
} else {
if (typeof URL === 'undefined') {
URL = window.location.href;
}
if (typeof Title === 'undefined') {
Title = document.title;
}
if (typeof Description === 'undefined') {
Description = 'Share your thoughts about ' + Title;
}
const TitleConst = Title;
const URLConst = URL;
const DescriptionConst = Description;

        try {
            await navigator.share({title: TitleConst, text: DescriptionConst, url: URLConst});
        } catch (error) {
            console.log('Error sharing: ' + error);
            return;
        }
    }
}

let shareBtn = document.querySelector('.share-content');
if (shareBtn) {
if (typeof navigator.share === 'undefined' || !navigator.share) {
let clipboard = new ClipboardJS(shareBtn);
let tooltip = shareBtn.querySelector('.is-tooltip');
clipboard.on('success', function(e) {
createPopper(shareBtn, tooltip, {
placement: 'top',
});

            tooltip.classList.add('is-active');
            window.setTimeout(ev => {
                tooltip.classList.remove('is-active');
            }, 4000)

        });
    } else {
        shareBtn.addEventListener('click', BodyEvent => {
            var meta_desc, meta_title, meta_url
            if (document.querySelector('meta[property="og:description"]') != null) {
                meta_desc = document.querySelector('meta[property="og:description"]').content;
            }
            if (document.querySelector('meta[property="og:title"]') != null) {
                meta_title = document.querySelector('meta[property="og:title"]').content;
            }
            if (document.querySelector('meta[property="og:url"]') != null) {
                meta_url = document.querySelector('meta[property="og:url"]').content;
            }
            AndroidNativeShare(meta_title, meta_url, meta_desc);

        });
    }
}

SHARE END

Google PageSpeed Insights uses a simulated device with a screen resolution of 412 x 732 pixels to evaluate mobile website performance. 
This resolution represents a common screen size for modern mobile devices, providing a realistic basis for analyzing mobile user experiences.

For tablet devices, Google PageSpeed Insights uses a screen resolution of 1024 x 768 pixels when simulating tablet-based testing. 
This resolution is representative of many popular tablets and is used to evaluate the performance of websites on larger mobile displays.

For desktop devices, Google PageSpeed Insights uses a screen resolution of 1350 x 940 pixels. 
This size is used to simulate a typical desktop browsing environment, allowing for an assessment of how well a website performs on larger screens.

For evaluating websites on larger screens, Google PageSpeed Insights uses a maximum device resolution of 1920 x 1080 pixels. 
This resolution is typical of many full HD monitors and represents a common benchmark for high-resolution displays in desktop environments.


JE POUR LES BALISES IMAGES EN BACKGROUND AVE TRANSPARENCE EN HAUT ET BAS MAIS LE METTRE POUR LES BACKGROUNDS DE ZONES

FAIRE UN INDEX AVEC UNE 100AINE D ACTUS ET OPTIMISER L'AFFICHAGE DES IMAGES (LA FUNCTION TWIG EST TROP LONGUE EN RETOUR)

SearchManager : INTL
FormDuplicateService : INTL
ExportService : INTL
SeoService : INTL

let backgroundSize = function (img) {
let section = img.closest('section');
let sectionHeight = section.clientHeight;
let imgHeight = img.clientHeight;
if (sectionHeight < imgHeight) {
section.style.height = imgHeight + 'px';
img.style.height = 'initial';
} else {
img.style.height = '100%';
}
}

let backgroundImages = document.querySelectorAll("img.background");
backgroundImages.forEach(function (img) {
backgroundSize(img);
});
window.addEventListener('resize', () => {
let backgroundImages = document.querySelectorAll("img.background");
backgroundImages.forEach(function (img) {
backgroundSize(img);
});
});

Revoir les thumbs envoyer dans la vue par exemple que max-width 575px dans core/image.html.twig pour le bloc media

TRADS : LOADER Front & BACK TRADS BY URI +

                if (method_exists($intl, 'setWebsite')) {
                    $intl->setWebsite($website); // A Virer
                } elseif (method_exists($intl, $intlData->setter)) {
                    $setter = $intlData->setter; // Et ça aussi ca de se faire auto avec la relation normalement
                    $intl->$setter($entity);
                }

generate lazy file with :

                        if (!$filesystem->exists($cropDir.$filename)) {
                            $originalImage = $isPngFile ? @imagecreatefrompng($dirname) : @imagecreatefromjpeg($dirname);
                            $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
                            imagecopyresampled($croppedImage, $originalImage, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight, $cropWidth, $cropHeight);
                            if ($isPngFile) {
                                imagealphablending($croppedImage, false);
                                imagesavealpha($croppedImage, true);
                                $transparentColor = imagecolorallocatealpha($croppedImage, 0, 0, 0, 127);
                                imagefilledrectangle($croppedImage, 0, 0, $cropWidth - 1, $cropHeight - 1, $transparentColor);
                                imagepng($croppedImage, $cropDir.$filename, 9);
                            } else {
                                imagejpeg($croppedImage, $cropDir.$filename, 100);
                            }
                            imagedestroy($originalImage);
                            imagedestroy($croppedImage);
                        }

UPDATE domains.cache.json on submit
UPDATE app-entity-......-listing.cache.json on submit

$thumbConfiguration = $this->thumbnailRuntime->thumbConfiguration($media, $thumbConfiguration); NO EXIST

COMMENTAIRE REPOSITORY + declare strict (Voir aussi entity)

FAIRE LE CROP des images comme dans core extension de grand forma

DARK THEME EN SESSION EN MODE PRIVE

REMOVE SEO SERVICE CACHE POOL ON SUBMIT
REMOVE WEBSITE MODEL CACHE POOL ON SUBMIT
REMOVE MENU SERVICE CACHE POOL ON SUBMIT

CHECKER LE SITEMAP.XML AVEC PLUSIEURS LOCALES


{#{{ dump('tester api values') }}#}
{#{{ dump('tester api values') }}#}
{#{{ dump('tester api values') }}#}
{#{{ dump('tester api values') }}#}
{#{{ dump('tester api values') }}#}
{#{{ dump('tester api values') }}#}
{#{{ dump('tester api values') }}#}

SPAM :
https://help.ovhcloud.com/csm/fr-web-hosting-monitoring-automatic-emails?id=kb_article_view&sysparm_article=KB0052902#letat-spam 

Suppression de meida dans actu ne fonctionne plus

Voir pour virer Website de Layout

Supprimer le cache des Models au submit

Ajouter Model dans Configuration pour l'interface service et le rcupérer en front pour gnérer le model sinon BaseModelView

WebsiteSubscriber SCFMS 6

$sitemapService->execute($website)

voir tous les ->getConfiguration()

virer les $hasArray ou $asArray

En front voir pour récupérer le model Website partout 

Vider le cache et checker les group de queries visiblement website call several times

Alignments des contenus cols zones pour tous les screens

, fetch: 'EAGER'

Stocker les block dans JSON

Cleaner le system de cache

Mettre le loader position dans les collections de medias comme Newscast

Voir les ->setIntl($intl);

Faire des models

CoreLocatorInterface $coreLocator dans service et Manager

recupérer la toto FC annecy

Faire des Interfaces pour chaque manger et les mettre dans le m^me dossier que le manger

Regenerate entities to pas : self to : static ex down
public function setPosition(?int $position): static

declare(strict_types=1);

https://chat.openai.com/c/704ba42a-ba3d-43c5-b62c-c3c85524c827
https://chat.openai.com/c/704ba42a-ba3d-43c5-b62c-c3c85524c827
https://chat.openai.com/c/704ba42a-ba3d-43c5-b62c-c3c85524c827

https://symfony.com/doc/5.x/frontend/ux.html
https://symfony.com/doc/5.x/frontend/ux.html
https://symfony.com/doc/5.x/frontend/ux.html
https://symfony.com/doc/5.x/frontend/ux.html
https://symfony.com/doc/5.x/frontend/ux.html

https://gudh.github.io/ihover/dist/
https://tympanus.net/Development/HoverEffectIdeas/
https://ianlunn.github.io/Hover/
https://freefrontend.com/css-image-effects/


https://github.com/rectorphp/rector

remove FC ANNECY

virer les box9 hoverTheme

Revoir la vue des video dans un slide et des medias

https://getbootstrap.com/docs/5.3/utilities/text/#sass-variables

dans trad récupérer pour vendor que validations

Ajouter folder
Ajouter folder
Ajouter folder TRADS dans medias gallerie

faire des modules packagists

replace {% include > {{ include(

remove {% import 'core/src-macro.html.twig' as resources %}

faire une librairie de btn hover avec clip-path et virer l'ancienne plus variables

tester les img hover librairies

checker ca {% if routeExist('admin_' ~ interface['name'] ~ '_layout') and editActive and """""entity.customLayout""""" is not defined %} dans index admin core

Best way to optimize Symfony Chat GPT: https://chat.openai.com/c/ba038353-cad7-416c-977f-29f01b92cc33

ul bullet by background

Active ./var/sessions

declare(strict_types=1); dans entités ????????????

remove model Website Redirection service

->toIterable

Model et appeler getI18n par bonne locale dedans media etc TESTER DANS ACTUALITES

Event EMail lumirama

boostrap 5 vars front

recaptcha Google

revoir le tpl category news

ThumbController Admin
$thumb = $thumbnailRuntime->thumbConfiguration($media, $thumbConfiguration); NOT EXIST

{% block form_row -%}

    {% set formGroup = attr.group is defined ? attr.group : 'col-12' %}
    {% set formGroup = attr['data-group'] is defined ? attr['data-group'] : formGroup %}
    {% set counterExist = counter is defined %}
    {% set hasColorPicker = attr.class is defined and 'colorpicker' in attr.class %}
    {% set display = display is defined ? display : null %}
    {% set invalid = (not compound or force_error|default(false)) and not valid %}
    {% set i18nLocale = vars.data.locale is defined ? vars.data.locale : (form.parent.vars.data.locale is defined ? form.parent.vars.data.locale : null)  %}

    {%- if compound is defined and compound -%}
        {%- set element = 'fieldset' -%}
    {%- endif -%}

    {%- if 'datetime' in block_prefixes or 'date' in block_prefixes -%}
        {%- set element = 'div' -%}
    {%- endif -%}

    {%- set widget_attr = {} -%}

    {%- if help is not empty -%}
        {%- set widget_attr = {attr: {'aria-describedby': id ~ "_help"}} -%}
    {%- endif -%}

    {%- if element|default('div') != 'fieldset' -%}
        <{{ element|default('div') }} class="form-group
        {% if formGroup %} {{ formGroup }}{% endif %}
        {% if i18nLocale %} {{ 'group-locale-' ~ i18nLocale }}{% endif %}
        {% if counterExist %} counter-form-group{% endif %}
        {% if invalid %} is-invalid{% endif %}
        {% if hasColorPicker %} colorpicker-group{% endif %}">
    {%- endif -%}

    {%- if display == 'floating' -%}
        <div class="form-floating">
            {{- form_widget(form, widget_attr) -}}
            {{- form_label(form) -}}
        </div>
    {%- else -%}
        {{- form_label(form) -}}
        {{- form_widget(form, widget_attr) -}}
    {%- endif -%}

    {%- if counterExist -%}
        <small class="char-counter mt-1 form-text text-info" data-limit="{{ counter }}">
            <span class="count">{{ value|striptags|length }}</span> / {{ counter }} {{ "caractères recommandés"|trans([], 'admin')|raw }}
        </small>
    {%- endif -%}

    {{- form_help(form) -}}

    {%- if element|default('div') != 'fieldset' -%}
        </{{ element|default('div') }}>
    {%- endif -%}

    {% set isMaterial = attr.class is defined and 'material' in attr.class %}
    {%- if errors|length > 0 and isMaterial -%}
{#        <div id="{{ id }}_errors" class="mb-2">#}
{{- form_errors(form) -}}
{#        </div>#}
{%- endif -%}

{%- endblock form_row %}

active GDPR webpack ??

functions export modules JS

icon twig like neosens et faire la verif /build sans le iconHtml in twig

Bootstrap 5

only twig include

virer media placeholder

store cache hint thumbnails fo front + cache date sur media

if (front_default.optimization && front_default.optimization.minimizer) {
front_default.optimization.minimizer.push(new CssMinimizerPlugin());
}

        mediaLazy1:
            quality: 1
            filters:
                format:
                    type: webp

generate thumbs que via le back process

https://cms6.local/actualites // Taille de images mobile tablet

{% if granted('ROLE_NEWSCAST') %}
{% include 'core/webmaster-edit.html.twig' with {
'title': "Éditer le teaser"|trans([], 'front_webmaster'),
'role': 'ROLE_NEWSCAST',
'path': path('admin_newscastteaser_edit', {'website': teaser.website.id, 'newscastteaser': teaser.id})
} only %}
{% endif %} PARTOUT

champs pictogram add placeholder

RECUPERER MODULES BANNIERE RADIO VAL

New boostrap 5 front

Remove Request comme dans i18nRuntime setRequest

VOIR POUR VIRER LES 2 METHODS du bas DE KERNEL (Voir pour faire un import yaml des services)

CLEAR ASSETS VENDOR / FRONT / MAIN ...