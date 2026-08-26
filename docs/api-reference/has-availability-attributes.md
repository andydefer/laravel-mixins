# HasAvailabilityAttributes - Référence Technique

## Description

Fournit des accesseurs Eloquent typés pour vérifier la disponibilité et les créneaux horaires d'un modèle (médecin, pharmacie, hôpital, etc.). S'appuie sur **Laravel Chronos** pour le moteur de planification.

## Hiérarchie

```
Trait
    └── HasAvailabilityAttributes
```

## Rôle principal

Ce trait ajoute une relation Eloquent et quatre attributs Eloquent à un modèle, permettant de vérifier la disponibilité d'une entité sans duplication de code. Il s'appuie sur le package **Laravel Chronos** pour effectuer les calculs.

La relation et les attributs disponibles sont :
- `availabilities` : Relation polymorphique vers les disponibilités
- `is_available_now` : Indique si l'entité est disponible maintenant
- `next_slot` : Prochain créneau disponible
- `has_availability_on_date` : Indique si l'entité a des disponibilités aujourd'hui
- `total_available_minutes` : Total des minutes disponibles aujourd'hui

## API / Méthodes publiques

### `availabilities(): MorphMany`

Retourne la relation polymorphique vers les disponibilités.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `MorphMany<Availability>` - Instance de la relation

**Exceptions :** Aucune

**Exemple :**
```php
$doctor = Doctor::find(1);
$availabilities = $doctor->availabilities;

foreach ($availabilities as $availability) {
    echo $availability->name;
}
```

---

### `isAvailableNow(): Attribute`

Retourne un attribut Eloquent qui vérifie si l'entité est disponible à l'instant présent.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<bool>` - Attribut Eloquent retournant un booléen

**Exceptions :** Aucune (les erreurs sont capturées et retournent false)

**Exemple :**
```php
$doctor = Doctor::find(1);
if ($doctor->is_available_now) {
    // Le médecin est disponible maintenant
}
```

---

### `nextSlot(): Attribute`

Retourne un attribut Eloquent qui fournit le prochain créneau disponible à partir de maintenant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<SlotVO|null>` - Attribut Eloquent retournant un `SlotVO` ou null

**Exceptions :** Aucune (les erreurs sont capturées et retournent null)

**Exemple :**
```php
$doctor = Doctor::find(1);
$nextSlot = $doctor->next_slot;

if ($nextSlot) {
    $start = $nextSlot->getStart()->toDateTimeString();
    $end = $nextSlot->getEnd()->toDateTimeString();
    echo "Prochain créneau : $start - $end";
}
```

---

### `hasAvailabilityOnDate(): Attribute`

Retourne un attribut Eloquent qui vérifie si l'entité a des disponibilités aujourd'hui.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<bool>` - Attribut Eloquent retournant un booléen

**Exceptions :** Aucune (les erreurs sont capturées et retournent false)

**Exemple :**
```php
$pharmacy = Pharmacy::find(1);
if ($pharmacy->has_availability_on_date) {
    // La pharmacie est ouverte aujourd'hui
}
```

---

### `totalAvailableMinutes(): Attribute`

Retourne un attribut Eloquent qui calcule le nombre total de minutes disponibles aujourd'hui.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<int>` - Attribut Eloquent retournant un entier

**Exceptions :** Aucune (les erreurs sont capturées et retournent 0)

**Exemple :**
```php
$hospital = Hospital::find(1);
$minutes = $hospital->total_available_minutes;
echo "Total disponible : $minutes minutes";
```

---

### `isSchedulable(): bool`

Méthode protégée déterminant si le modèle peut être planifié. À surcharger dans le modèle pour ajouter des conditions personnalisées.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `bool` - True par défaut

**Exceptions :** Aucune

**Exemple :**
```php
// Dans le modèle Doctor
protected function isSchedulable(): bool
{
    return $this->is_active && $this->user_type->isDoctor();
}
```

## Cas d'utilisation

### Cas 1 : Affichage de la disponibilité d'un médecin

**Problème :** Afficher sur le profil d'un médecin s'il est disponible maintenant et le prochain créneau.

**Solution :** Utiliser les attributs du trait dans la vue.

```php
// Dans le contrôleur
$doctor = Doctor::find(1);

return view('doctor.profile', [
    'doctor' => $doctor,
    'isAvailable' => $doctor->is_available_now,
    'nextSlot' => $doctor->next_slot,
]);
```

### Cas 2 : Affichage des horaires d'une pharmacie

**Problème :** Afficher les horaires d'ouverture d'une pharmacie.

**Solution :** Utiliser `has_availability_on_date` et `total_available_minutes`.

```php
$pharmacy = Pharmacy::find(1);

$status = $pharmacy->has_availability_on_date
    ? 'Ouverte aujourd\'hui'
    : 'Fermée aujourd\'hui';

$hours = $pharmacy->total_available_minutes / 60;
echo "{$status} - {$hours}h de disponibilité";
```

### Cas 3 : Liste des médecins disponibles aujourd'hui

**Problème :** Récupérer tous les médecins disponibles aujourd'hui.

**Solution :** Filtrer avec `has_availability_on_date`.

```php
$doctors = Doctor::all()->filter(fn($doctor) => $doctor->has_availability_on_date);
```

### Cas 4 : Récupération des disponibilités d'un modèle

**Problème :** Récupérer toutes les disponibilités d'un médecin.

**Solution :** Utiliser la relation `availabilities()`.

```php
$doctor = Doctor::find(1);
$availabilities = $doctor->availabilities;

foreach ($availabilities as $availability) {
    echo $availability->name . ' : ' . $availability->daily_start . ' - ' . $availability->daily_end;
}
```

### Cas 5 : Surcharge de `isSchedulable()` pour des conditions métier

**Problème :** Un médecin ne doit être planifiable que s'il est actif et accepte de nouveaux patients.

**Solution :** Surcharger `isSchedulable()` dans le modèle.

```php
// Dans le modèle Doctor
protected function isSchedulable(): bool
{
    return $this->is_active 
        && $this->user_type->isDoctor()
        && $this->doctorProfile?->is_accepting_new_patients->isYes();
}
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Erreur lors du calcul (service indisponible) | Capturée | Retourne la valeur par défaut (false, null, 0) |
| Modèle non planifiable | - | Retourne la valeur par défaut |
| Exception générale | Capturée | Retourne la valeur par défaut |

**Note :** Toutes les exceptions sont capturées en interne pour éviter que l'application ne plante. Les valeurs par défaut sont retournées silencieusement.

## Intégration

### Avec Laravel Chronos

Ce trait utilise le `SlotService` et `ChronosConfig` du package `andydefer/laravel-chronos`.

```php
$slotService = app(SlotServiceInterface::class);
$config = app(ChronosConfigInterface::class);
$duration = $config->getMinSlotSearchDuration();

$slots = $slotService->findSlotsForDay($this, DateTimeZuluVO::today(), $duration);
$nextSlot = $slotService->findNextSlot($this, DateTimeZuluVO::now(), $duration);
$hasAvailability = $slotService->hasAvailabilityOnDate($this, DateTimeZuluVO::today());
```

### Avec un modèle Eloquent

```php
use AndyDefer\Mixins\Traits\HasAvailabilityAttributes;

final class Doctor extends Model
{
    use HasAvailabilityAttributes;

    protected function isSchedulable(): bool
    {
        return $this->is_active && $this->user_type->isDoctor();
    }
}
```

## Performance

- **Mise en cache :** Les attributs sont calculés à chaque accès. Pour des performances optimales, envisager un cache :

```php
public function nextSlot(): Attribute
{
    return Attribute::make(
        get: function (): ?SlotVO {
            return Cache::remember(
                "next_slot_{$this->id}",
                300,
                fn() => app(SlotServiceInterface::class)->findNextSlot(...)
            );
        }
    );
}
```

- **Requêtes :** `findSlotsForDay()` peut être lourd sur de grandes périodes. La configuration `chronos.default_search_days` limite la recherche.

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| PHP 7.4 | ❌ Non supporté (nécessite PHP 8.0+) |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\Mixins\Traits\HasAvailabilityAttributes;
use Illuminate\Database\Eloquent\Model;

final class Doctor extends Model
{
    use HasAvailabilityAttributes;

    protected $fillable = ['name', 'is_active', 'user_type'];

    protected function isSchedulable(): bool
    {
        return $this->is_active && $this->user_type->isDoctor();
    }
}

// Création d'un médecin
$doctor = Doctor::create([
    'name' => 'Dr. Jean Dupont',
    'is_active' => true,
    'user_type' => 'doctor',
]);

// Création d'une disponibilité (via Laravel Chronos)
$availabilityService = app(AvailabilityServiceInterface::class);
$availabilityService->for($doctor)->create(
    AvailabilityRecord::from([
        'name' => 'Consultations',
        'days' => ['monday', 'wednesday', 'friday'],
        'daily_start' => '09:00:00',
        'daily_end' => '17:00:00',
        'validity_start' => '2024-01-01T00:00:00Z',
        'validity_end' => '2024-12-31T23:59:59Z',
    ])
);

// Récupération des disponibilités
$availabilities = $doctor->availabilities;

// Récupération des données
echo $doctor->is_available_now;        // true (si actuellement dans les horaires)
$nextSlot = $doctor->next_slot;         // SlotVO
echo $doctor->has_availability_on_date; // true
echo $doctor->total_available_minutes;  // 480 (8 heures)
```

## Voir aussi

- `HasRatingAttributes` - Attributs d'évaluation
- `Laravel Chronos` - Moteur de planification
- `SlotServiceInterface` - Service de recherche de créneaux
- `ChronosConfigInterface` - Configuration du moteur de planification
- `SlotVO` - Value Object représentant un créneau
- `Availability` - Modèle de disponibilité
```