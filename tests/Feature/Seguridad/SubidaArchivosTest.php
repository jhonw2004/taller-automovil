<?php

use App\Filament\Erp\Resources\TallerResource;
use App\Filament\Erp\Resources\TallerResource\Pages\EditTaller;
use Filament\Schemas\Schema;

/**
 * 020-seguridad-produccion §B: antes de agregar `->maxSize()`/`->acceptedFileTypes()` al
 * `FileUpload::make('logo_url')` de `TallerResource`, el único límite real era
 * `upload_max_filesize`/`post_max_size` de PHP a nivel de servidor — sin tope de aplicación.
 *
 * Se verifica la configuración del componente directamente sobre el `Schema` en vez de a través
 * de `Livewire::test(EditTaller::class, ...)->fillForm()`: ese Resource (edición del taller
 * activo, no un listado) tiene una particularidad propia del harness de testing de Livewire en
 * este entorno — confirmado reproduciéndolo también contra el código sin modificar, no es un
 * efecto de este cambio — que hace que la segunda fase de hidratación de la request de prueba
 * devuelva 403 pese a que `canEdit()` es `true` (verificado por separado). Ningún test existente
 * en el proyecto ejercita esa página vía Livewire todavía. Esta forma alternativa verifica
 * exactamente lo mismo (la configuración real que usa el formulario) sin depender de esa ruta
 * frágil, instanciando la página sin montarla (sin sesión/auth) solo como dueño del `Schema`.
 */
it('el logo del taller tiene limite de tamano y whitelist de mime types', function () {
    $schema = TallerResource::form(Schema::make(new EditTaller));
    $componente = $schema->getComponentByStatePath('logo_url', withHidden: true);

    expect($componente)->not->toBeNull();
    expect($componente->getMaxSize())->toBe(2048);
    expect($componente->getAcceptedFileTypes())->toBe(['image/jpeg', 'image/png', 'image/webp']);
});
