# Intégration Axonaut (CRM)

Envoi automatique des contacts soumis via le module **Form** vers le CRM
[Axonaut](https://axonaut.com/api/v2/doc), sous forme de **société (prospect)**,
**contact (employee)** et **opportunité**. L'envoi est **asynchrone** et
**activable formulaire par formulaire**.

---

## Fonctionnement

1. Un visiteur soumet un formulaire du module Form.
2. `FormManager::success()` crée le contact, puis — si l'option est activée sur
   la configuration du formulaire — dispatch un message asynchrone
   `PushContactToAxonaut`.
3. Le handler `PushContactToAxonautHandler` appelle l'API Axonaut dans l'ordre :
   - `POST /companies` → société marquée `is_prospect`
   - `POST /employees` → contact rattaché à la société
   - `POST /opportunities` → opportunité rattachée à la société

L'appel est **défensif** : toute erreur réseau ou API est journalisée sans
jamais interrompre ni faire échouer la soumission du formulaire.

---

## Activation

### 1. Variables d'environnement

À renseigner dans `.env.local` / `.env.prod` (modèle dans `.env.dist`) :

```dotenv
# Interrupteur général. À false, aucun contact n'est envoyé.
AXONAUT_ENABLED=true
# Clé d'API générée dans le dashboard Axonaut (section « API »).
AXONAUT_API_KEY=votre_cle_api
# Pipeline / étape d'atterrissage des opportunités (libellés propres au compte).
AXONAUT_OPPORTUNITY_PIPE=
AXONAUT_OPPORTUNITY_STEP=
# Montant par défaut de l'opportunité (0 = non défini).
AXONAUT_OPPORTUNITY_AMOUNT=0
```

> `AXONAUT_ENABLED=false` **ou** une clé API vide désactive complètement l'envoi.

### 2. Migration de base de données

```bash
php bin/console doctrine:migrations:migrate
```

Ajoute la colonne `axonautEnabled` sur `module_form_configuration`.

### 3. Activation par formulaire

Dans l'admin, configuration du formulaire → cocher
**« Envoyer les contacts vers Axonaut »** (option réservée aux utilisateurs
internes).

---

## Mapping des champs

Les champs du formulaire sont reconnus **par leur slug** (insensible à la casse) :

| Donnée Axonaut | Slugs reconnus                                                  |
|----------------|-----------------------------------------------------------------|
| `firstname`    | `firstname`, `first-name`, `first_name`, `prenom`, `prénom`     |
| `lastname`     | `lastname`, `last-name`, `last_name`, `nom`, `name`             |
| `company`      | `company`, `societe`, `société`, `entreprise`, `organisation`   |
| `comments`     | `message`, `comment`, `comments`, `commentaire`, `demande`      |

- **Email** et **téléphone** sont repris directement du contact enregistré
  (résolus pendant `addContact()`), pas du mapping de slug.
- **Nom de la société** envoyée : champ « société » s'il existe, sinon
  « Prénom Nom », sinon l'email, sinon `Contact site web`.
- **Nom de l'opportunité** : le nom administrable du formulaire.

---

## Architecture

| Élément | Fichier |
|---------|---------|
| Client API | `src/Service/Axonaut/AxonautClient.php` (+ `AxonautClientInterface`) |
| Message asynchrone | `src/Message/Axonaut/PushContactToAxonaut.php` |
| Handler | `src/MessageHandler/Axonaut/PushContactToAxonautHandler.php` |
| Branchement | `src/Form/Manager/Front/FormManager.php` → `pushToAxonaut()` |
| Flag de config | `src/Entity/Module/Form/Configuration.php` (`axonautEnabled`) |
| Option admin | `src/Form/Type/Module/Form/ConfigurationType.php` |
| Routing Messenger | `config/packages/messenger.yaml` (transport `async`) |
| Enregistrement service | `config/services.yaml` |

Le client s'appuie sur `symfony/http-client` (composant du framework, comme les
providers de traduction DeepL / LibreTranslate). Aucune dépendance externe :
il n'existe aucun package Composer / bundle Symfony pour Axonaut.

---

## Journalisation

Toutes les opérations sont écrites dans `var/log/axonaut-handler.log`
(rotation sur 10 fichiers). En cas d'échec, la **requête et la réponse** sont
journalisées — utile pour caler les noms de champs Axonaut au premier test réel.

---

## Points de vigilance

- **Schéma API** : faute de SDK officiel, les noms de champs suivent la doc
  Axonaut v2 (`is_prospect`, `firstname`, `phone_number`, `pipe`, `step`,
  `amount`…). À vérifier via les logs lors du premier envoi, et ajuster dans
  `AxonautClient` si le compte diffère (notamment `pipe`/`step`).
- **Conformité (RGPD)** : l'envoi transmet des données personnelles (nom,
  email, téléphone) à un tiers. À intégrer dans le registre de traitement et les
  mentions légales, et à conditionner au consentement le cas échéant.
- **Pré-requis worker** : l'envoi étant asynchrone, le worker Messenger doit
  tourner (`messenger:consume async`) ou être déclenché en arrière-plan
  (mécanisme déjà en place via `MessengerWorkerService`).
