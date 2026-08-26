# Laravel Mixins

> Collection de traits Eloquent réutilisables pour Laravel

Un package Laravel qui fournit des traits pour ajouter rapidement des attributs de disponibilité et d'évaluation à vos modèles Eloquent.

---

## 📋 Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
  - [HasAvailabilityAttributes](#hasavailabilityattributes)
  - [HasRatingAttributes](#hasratingattributes)
- [Référence de l'API](#référence-de-lapi)
  - [HasAvailabilityAttributes](#hasavailabilityattributes-api)
  - [HasRatingAttributes](#hasratingattributes-api)
- [Exemples complets](#exemples-complets)
- [Tests](#tests)
- [Contribuer](#contribuer)
- [Licence](#licence)

---

## ✨ Fonctionnalités

- ✅ **HasAvailabilityAttributes** - Attributs de disponibilité pour les modèles planifiables
- ✅ **HasRatingAttributes** - Attributs d'évaluation pour les modèles notables
- ✅ **Attributs Eloquent typés** - Accès direct aux propriétés du modèle
- ✅ **Value Objects** - Retourne des objets typés (SlotVO, DateTimeZuluVO)
- ✅ **Intégration native** - Fonctionne avec Laravel Chronos et Laravel Ratings
- ✅ **Personnalisable** - Surchargez `isSchedulable()` et `isRateable()` pour des conditions métier
- ✅ **0 configuration** - Fonctionne immédiatement après installation

---

## 🚀 Prérequis

- PHP 8.1 ou supérieur
- Laravel 12.0, 13.0, 14.0 ou 15.0
- [`andydefer/laravel-chronos`](https://github.com/andydefer/laravel-chronos) ^1.0
- [`andydefer/laravel-ratings`](https://github.com/andydefer/laravel-ratings) ^1.0

---

## 📦 Installation

Installez le package via Composer :

```bash
composer require andydefer/laravel-mixins
```

### Publier la configuration (optionnel)

```bash
php artisan vendor:publish --tag=mixins-config
```

---

## ⚙️ Configuration

Le package est automatiquement découvert par Laravel. Aucune configuration supplémentaire n'est requise.

Si vous devez personnaliser le Service Provider, ajoutez-le manuellement dans `config/app.php` :

```php
'providers' => [
    // ...
    AndyDefer\Mixins\MixinsServiceProvider::class,
],
```

### Fichier de configuration

```php
// config/mixins.php

return [
    'slot_duration' => env('MIXINS_SLOT_DURATION', 30),
    'min_slot_duration' => env('MIXINS_MIN_SLOT_DURATION', 15),
];
```

---

## 📖 Utilisation

### HasAvailabilityAttributes

Ajoutez le trait à votre modèle pour bénéficier des attributs de disponibilité.

```php
use AndyDefer\Mixins\Traits\HasAvailabilityAttributes;
use Illuminate\Database\Eloquent\Model;

final class Doctor extends Model
{
    use HasAvailabilityAttributes;

    // Surchargez cette méthode pour ajouter des conditions personnalisées
    protected function isSchedulable(): bool
    {
        return $this->is_active && $this->user_type->isDoctor();
    }
}
```

#### Attributs disponibles

| Attribut | Type | Description |
|----------|------|-------------|
| `$model->is_available_now` | `bool` | L'entité est-elle disponible maintenant ? |
| `$model->next_slot` | `SlotVO\|null` | Prochain créneau disponible |
| `$model->has_availability_on_date` | `bool` | L'entité a-t-elle des disponibilités aujourd'hui ? |
| `$model->total_available_minutes` | `int` | Total des minutes disponibles aujourd'hui |

```php
$doctor = Doctor::find(1);

// Vérifier la disponibilité immédiate
if ($doctor->is_available_now) {
    echo "Le médecin est disponible maintenant";
}

// Récupérer le prochain créneau
$nextSlot = $doctor->next_slot;
if ($nextSlot) {
    $start = $nextSlot->getStart()->toDateTimeString();
    $end = $nextSlot->getEnd()->toDateTimeString();
    echo "Prochain créneau : $start - $end";
}

// Vérifier les disponibilités du jour
if ($doctor->has_availability_on_date) {
    $minutes = $doctor->total_available_minutes;
    echo "Disponible aujourd'hui : $minutes minutes";
}
```

### HasRatingAttributes

Ajoutez le trait à votre modèle pour bénéficier des attributs d'évaluation.

```php
use AndyDefer\Mixins\Traits\HasRatingAttributes;
use Illuminate\Database\Eloquent\Model;

final class Product extends Model
{
    use HasRatingAttributes;

    // Surchargez cette méthode pour ajouter des conditions personnalisées
    protected function isRateable(): bool
    {
        return $this->is_active && $this->status === 'published';
    }
}
```

#### Attributs disponibles

| Attribut | Type | Description |
|----------|------|-------------|
| `$model->average_rating` | `float` | Note moyenne (0.0 si aucune note) |
| `$model->rating_count` | `int` | Nombre total d'évaluations |
| `$model->rating_distribution` | `array` | Distribution des notes par niveau (1-5) |
| `$model->has_ratings` | `bool` | Le modèle a-t-il des évaluations ? |

```php
$product = Product::find(1);

// Afficher la note moyenne
echo "Note moyenne : {$product->average_rating} / 5";

// Afficher le nombre d'avis
echo "{$product->rating_count} avis";

// Vérifier la présence d'avis
if ($product->has_ratings) {
    $distribution = $product->rating_distribution;
    // [1 => 2, 2 => 5, 3 => 10, 4 => 32, 5 => 78]
}
```

---

## 📚 Référence de l'API

### HasAvailabilityAttributes API

#### `isAvailableNow(): Attribute<bool>`

Vérifie si l'entité est disponible à l'instant présent.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<bool>`

**Exceptions :** Aucune (les erreurs sont capturées)

**Exemple :**
```php
if ($doctor->is_available_now) {
    // Le médecin est disponible
}
```

---

#### `nextSlot(): Attribute<SlotVO|null>`

Retourne le prochain créneau disponible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<SlotVO|null>`

**Exceptions :** Aucune (les erreurs sont capturées)

**Exemple :**
```php
$slot = $doctor->next_slot;
if ($slot) {
    echo $slot->getStart()->toDateTimeString();
}
```

---

#### `hasAvailabilityOnDate(): Attribute<bool>`

Vérifie si l'entité a des disponibilités aujourd'hui.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<bool>`

**Exceptions :** Aucune (les erreurs sont capturées)

**Exemple :**
```php
if ($pharmacy->has_availability_on_date) {
    echo "La pharmacie est ouverte aujourd'hui";
}
```

---

#### `totalAvailableMinutes(): Attribute<int>`

Retourne le total des minutes disponibles aujourd'hui.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<int>`

**Exceptions :** Aucune (les erreurs sont capturées)

**Exemple :**
```php
$hours = $doctor->total_available_minutes / 60;
echo "Disponible {$hours}h aujourd'hui";
```

---

### HasRatingAttributes API

#### `averageRating(): Attribute<float>`

Retourne la note moyenne du modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<float>`

**Exceptions :** Aucune (les erreurs sont capturées)

**Exemple :**
```php
echo $product->average_rating; // 4.5
```

---

#### `ratingCount(): Attribute<int>`

Retourne le nombre total d'évaluations.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<int>`

**Exceptions :** Aucune (les erreurs sont capturées)

**Exemple :**
```php
echo $product->rating_count; // 42
```

---

#### `ratingDistribution(): Attribute<array<int, int>>`

Retourne la distribution des notes par niveau (1 à 5).

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<array<int, int>>`

**Exceptions :** Aucune (les erreurs sont capturées)

**Exemple :**
```php
$distribution = $product->rating_distribution;
// [1 => 0, 2 => 0, 3 => 1, 4 => 2, 5 => 5]
```

---

#### `hasRatings(): Attribute<bool>`

Indique si le modèle a au moins une évaluation.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<bool>`

**Exceptions :** Aucune (les erreurs sont capturées)

**Exemple :**
```php
if ($product->has_ratings) {
    // Afficher les évaluations
}
```

---

## 🔍 Exemples complets

### Modèle Doctor avec les deux traits

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\Mixins\Traits\HasAvailabilityAttributes;
use AndyDefer\Mixins\Traits\HasRatingAttributes;
use Illuminate\Database\Eloquent\Model;

final class Doctor extends Model
{
    use HasAvailabilityAttributes;
    use HasRatingAttributes;

    protected $fillable = [
        'name',
        'email',
        'is_active',
        'user_type',
    ];

    protected function isSchedulable(): bool
    {
        return $this->is_active && $this->user_type === 'doctor';
    }

    protected function isRateable(): bool
    {
        return $this->is_active && $this->user_type === 'doctor';
    }
}
```

### Contrôleur d'API

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Doctor;
use Illuminate\Http\JsonResponse;

final class DoctorController
{
    public function show(Doctor $doctor): JsonResponse
    {
        return response()->json([
            'id' => $doctor->id,
            'name' => $doctor->name,
            'email' => $doctor->email,
            'available_now' => $doctor->is_available_now,
            'next_slot' => $doctor->next_slot,
            'has_availability_today' => $doctor->has_availability_on_date,
            'available_minutes' => $doctor->total_available_minutes,
            'average_rating' => $doctor->average_rating,
            'rating_count' => $doctor->rating_count,
            'rating_distribution' => $doctor->rating_distribution,
            'has_ratings' => $doctor->has_ratings,
        ]);
    }
}
```

### Liste des médecins disponibles

```php
$doctors = Doctor::all()->filter(fn($doctor) => $doctor->has_availability_on_date);

foreach ($doctors as $doctor) {
    echo $doctor->name . ' - ' . $doctor->average_rating . '⭐';
}
```

---

## 🧪 Tests

### Exécuter les tests

```bash
composer test
```

### Exécuter uniquement les tests d'intégration

```bash
composer test-integration
```

### Structure des tests

```
tests/
├── Fixtures/
│   ├── migrations/
│   │   └── 0001_00_00_000001_create_test_tables.php
│   └── Models/
│       ├── TestCar.php
│       ├── TestPost.php
│       └── TestUser.php
├── Integration/
│   └── Traits/
│       ├── HasAvailabilityAttributesTest.php
│       └── HasRatingAttributesTest.php
└── IntegrationTestCase.php
```

---

## 🔧 Développement

### Style de code

```bash
./vendor/bin/pint
```

### Analyse statique

```bash
./vendor/bin/phpstan analyse
```

---

## 🤝 Contribuer

Veuillez consulter [CONTRIBUTING](CONTRIBUTING.md) pour plus de détails.

### Flux de développement

1. Forkez le dépôt
2. Créez une branche de fonctionnalité (`git checkout -b feature/amazing-feature`)
3. Apportez vos modifications
4. Exécutez les tests (`composer test`)
5. Committez vos modifications (`git commit -m 'Ajouter une fonctionnalité géniale'`)
6. Poussez vers la branche (`git push origin feature/amazing-feature`)
7. Ouvrez une Pull Request

---

## 📦 Dépendances

- [`andydefer/laravel-chronos`](https://github.com/andydefer/laravel-chronos) ^1.0 - Moteur de planification
- [`andydefer/laravel-ratings`](https://github.com/andydefer/laravel-ratings) ^1.0 - Système d'évaluation

---

## 👨‍💻 Auteur

**Andy Defer**
- GitHub: [@andydefer](https://github.com/andydefer)

---

## 📄 Licence

Ce package est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus d'informations.

---

## ⭐ Support

Si vous trouvez ce package utile, n'hésitez pas à lui donner une ⭐ sur GitHub !

---

**Construit avec ❤️ pour la communauté Laravel**