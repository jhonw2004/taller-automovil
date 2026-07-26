<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\TallerResource\Pages\EditTaller;
use App\Filament\Erp\Resources\TallerResource\Pages\ListTalleres;
use App\Models\Taller;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Edición del propio taller (003-gestion-talleres/spec.md), no un listado: un usuario ERP
 * solo edita el taller activo de su sesión. `canCreate()=false` (la creación de talleres pasa
 * por el flujo de `004-solicitud-alta-taller`, fuera de orden todavía) y `canDelete()=false`
 * (el soft delete de un taller es exclusivo del Super Admin, ver `TallerResource` de /admin).
 *
 * `visible_en_mapa` NO es un campo del formulario: se cambia con un Action dedicado en la
 * página de edición que invoca `CambiarVisibilidadTallerAction` (valida lat/lon, audita), en
 * vez de dejar que el guardado normal del formulario escriba el campo directo.
 */
class TallerResource extends Resource
{
    protected static ?string $model = Taller::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Mi taller';

    protected static ?string $slug = 'taller';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id', session('taller_activo_id'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @param Taller $record */
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->tienePermiso('taller.ver', session('taller_activo_id')) ?? false;
    }

    /** @param Taller $record */
    public static function canEdit($record): bool
    {
        $user = Filament::auth()->user();

        return $user?->tienePermiso('taller.editar', session('taller_activo_id'))
            || $user?->tienePermiso('taller.configurar', session('taller_activo_id'))
            ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Taller')->tabs([
                Tab::make('Datos generales')->schema([
                    TextInput::make('nombre')->required()->maxLength(255),
                    Textarea::make('descripcion')->maxLength(2000)->columnSpanFull(),
                    TextInput::make('telefono')->tel()->maxLength(30),
                    TextInput::make('email')->email()->maxLength(255),
                    TextInput::make('nit')->maxLength(50),
                    TextInput::make('direccion')->maxLength(255)->columnSpanFull(),
                    FileUpload::make('logo_url')
                        ->label('Logo')
                        ->disk('public')
                        ->directory('talleres/logos')
                        ->image()
                        ->columnSpanFull(),
                ])->columns(2),
                Tab::make('Ubicación')->schema([
                    TextInput::make('lat')
                        ->label('Latitud')
                        ->numeric()
                        ->minValue(-90)
                        ->maxValue(90)
                        ->required(),
                    TextInput::make('lon')
                        ->label('Longitud')
                        ->numeric()
                        ->minValue(-180)
                        ->maxValue(180)
                        ->required(),
                ])->columns(2),
                Tab::make('Categorías')->schema([
                    CheckboxList::make('categorias')
                        ->label('')
                        ->relationship(
                            titleAttribute: 'nombre',
                            modifyQueryUsing: fn (Builder $query) => $query->where('activo', true),
                        )
                        ->searchable()
                        ->columns(2),
                ]),
                Tab::make('Horarios')->schema([
                    Repeater::make('horarios')
                        ->relationship()
                        ->schema([
                            Select::make('dia_semana')
                                ->label('Día')
                                ->options([
                                    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
                                    5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
                                ])
                                ->required()
                                ->distinct(),
                            Toggle::make('cerrado')->live()->default(false),
                            TimePicker::make('hora_apertura')
                                ->required(fn (callable $get) => ! $get('cerrado'))
                                ->hidden(fn (callable $get) => $get('cerrado')),
                            TimePicker::make('hora_cierre')
                                ->required(fn (callable $get) => ! $get('cerrado'))
                                ->hidden(fn (callable $get) => $get('cerrado'))
                                ->after('hora_apertura'),
                        ])
                        ->columns(4)
                        ->maxItems(7)
                        ->addActionLabel('Agregar día')
                        ->columnSpanFull(),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre'),
                TextColumn::make('estado')->badge(),
                TextColumn::make('visible_en_mapa')->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Visible' : 'Oculto')
                    ->color(fn (bool $state) => $state ? 'success' : 'gray'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTalleres::route('/'),
            'edit' => EditTaller::route('/{record}/edit'),
        ];
    }
}
