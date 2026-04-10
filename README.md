# Score Ticker (visualizador de resultados)

Plugin de WordPress que mostra un **ticker horizontal** con resultados de partidos (dous equipos e marcador), lendo os datos da **base de datos** (a mesma que usa WordPress).

## Requisitos

- WordPress recente (recomendado 5.x ou superior)
- PHP con MySQL/MariaDB (como calquera instalación estábel de WordPress)

## Instalación

### Opción A: Subir un ZIP desde o escritorio de WordPress

1. Comprime a carpeta do plugin (a que contén `score-ticker.php`) nun ficheiro `.zip`.
2. No escritorio: **Complementos → Engadir novo → Subir complemento**.
3. Escolle o ZIP e pulsa **Instalar agora**, despois **Activar complemento**.

### Opción B: FTP ou xestor de ficheiros do aloxamento

1. Sube a carpeta completa do plugin a:  
   `wp-content/plugins/`  
   (por exemplo: `wp-content/plugins/wordpress-score-ticker/`).
2. No escritorio: **Complementos** e activa **Score Ticker (resultados)**.

Ao activar, o plugin crea automaticamente a táboa na base de datos (se non existía).

### Crear a táboa a man (opcional)

Se prefires usar **phpMyAdmin** ou outro cliente SQL:

1. Abre a base de datos que usa WordPress (a de `wp-config.php`).
2. Sustitúe `wp_` polo **prefixo real** das táboas do teu sitio (`$table_prefix` en `wp-config.php`).
3. Executa o contido de `sql/create.sql`.
4. Opcional: insire datos de proba con `sql/insert.sql` (tamén adaptando o prefixo se non é `wp_`).

## Datos dos partidos

- No escritorio aparece o menú **Resultados ticker** (icona no menú lateral).
- Alí podes **engadir, editar e eliminar** partidos; os datos gárdanse na táboa  
  `{prefixo}score_ticker_matches`  
  (por exemplo `wp_score_ticker_matches`).

## Como facer que se vexa na web

### Shortcode (recomendado)

Engade onde queiras que apareza o ticker:

```
[score_ticker]
```

**Exemplos:**

- **Editor de bloques:** engade un bloque **Shortcode** e escribe `[score_ticker]`.
- **Widgets** (se o tema o permite): bloque de texto ou HTML co shortcode.
- **Cabecera entre logo e contido:** depende do tema. En temas clásicos podes editar `header.php` e inserir, por exemplo:

```php
<?php echo do_shortcode('[score_ticker]'); ?>
```

(En temas fillo é mellor facelo no fillo para non perder os cambios ao actualizar o tema.)

- **Elementor ou outros construtores:** widget **Shortcode** co mesmo texto `[score_ticker]`.

### Layout (logo + ticker)

O ticker xa vai en horizontal con frechas. Para poñelo **ao carón do logo**, envolve logo e shortcode nun contedor con flexbox no teu CSS do tema, por exemplo:

```html
<div class="cabecera-con-ticker" style="display:flex;align-items:stretch;">
  <!-- aquí o logo ou o menú -->
  <?php echo do_shortcode('[score_ticker]'); ?>
</div>
```

Axusta as clases e o HTML ao teu tema.

## API REST (referencia)

O ticker no navegador obtén os datos de:

`/wp-json/score-ticker/v1/matches`

Non é necesario configurar nada extra para o uso normal co shortcode.

## Soporte

Se o ticker non carga datos, comproba que a táboa exista, que haxa partidos rexistrados no menú **Resultados ticker** e que non haxa un plugin de seguridade bloqueando a REST API.
