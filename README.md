# System Information Bundle

Simple Sonata Admin bundle to get a system information overview. 

![Screenshot](docs/img/screenshot.png "Screenshot")

This bundle combines the functionalities of the following bundles and displays the aggregated information within the backend:

- [LiipMonitorBundle](https://github.com/liip/LiipMonitorBundle)

## Install

Follow the steps to enable the system information overview in the sonata backend.

### Composer

Install bundle via composer
```bash
$ composer require kmi/system-information-bundle
```

### Routing

Add a routing entry in `config/routes/kmi_system_information.yaml`
```yaml
kmi_system_information:
  resource: "@SystemInformationBundle/Resources/config/routing.yaml"
```

Extend the file with the routing definition of the LiipMonitorBundle
```yaml
_monitor:
  resource: "@LiipMonitorBundle/Resources/config/routing.xml"
  prefix: /monitor/health
```

### Templates

Add a twig entry in `config/packages/twig.yaml`
```yaml
paths:
  '%kernel.project_dir%/vendor/kmi/system-information-bundle/src/Resources/views': SystemInformationBundle
```

### Sonata Admin Menu

Add a sonata admin menu entry in `config/packages/sonata_admin.yaml`
```yaml
sonata_admin:
    dashboards:
        groups:
            app.admin.group.system:
                label: 'System'
                icon: '<i class="fa fa-cogs" aria-hidden="true"></i>'
                roles: ['ROLE_SUPER_ADMIN']
                on_top: true
                items:
                    - route: kmi_system_information_overview
                      label: System
```

### 

Install assets
```bash
$ php bin/console assets:install
$ php bin/console cache:clear
```

### Register checks

Configure [LiipMonitorBundle](https://github.com/liip/LiipMonitorBundle) in `config/packages/monitor.yaml`.

See an example in [monitor.yaml](docs/examples/monitor.yaml)

### Ready

Access the system overview page `/admin/system`.

## Functions

### System Indicator

Extend the Sonata Admin `standard_layout.html.twig` to enable the twig function in the backend header:

```html
{% block sonata_top_nav_menu %}
    <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
            <li>
                {{ system_information_indicator()|raw }}
            </li>
        </ul>
    </div>
{% endblock %}
```

### App Version

Displays the application version defined in the `composer.json` file:

```html
{{ version() }}
```

### Environment Indicator

Extend the Sonata Admin `standard_layout.html.twig` to enable the twig function in the backend header:

```html
{% extends '@!SonataAdmin/standard_layout.html.twig' %}
{% block sonata_breadcrumb %}
    <ul class="nav navbar-nav" style="float:left">
        {{ environment()|raw }}
    </ul>
    {{ parent() }}
{% endblock %}
```

Extend the Sonata User `login.html.twig` to enable the twig function in the login screen:

```html
{% extends '@!SonataUser/Admin/Security/login.html.twig' %}

{% block sonata_wrapper %}
    {{ parent() }}
    <ul style="position: absolute;top: 10px;left: 10px;">
        {{ environment()|raw }}
    </ul>
{% endblock sonata_wrapper %}
```