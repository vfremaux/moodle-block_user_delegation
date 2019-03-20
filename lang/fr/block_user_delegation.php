<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

$string['user_delegation:addinstance'] = 'Peut ajouter une instance';
$string['user_delegation:myaddinstance'] = 'Peut ajouter une instance aux pages personnalisées';
$string['user_delegation:canbulkaddusers'] = 'Peut importer des utilisateurs';
$string['user_delegation:cancreateusers'] = 'Peut créer des utilisateurs';
$string['user_delegation:candeleteusers'] = 'Peut supprimer des utilisateurs';
$string['user_delegation:configure'] = 'Peut configuer le bloc';
$string['user_delegation:isbehalfof'] = 'Est responsable de';
$string['user_delegation:hasasbehalf'] = 'A comme responsable';
$string['user_delegation:owncourse'] = 'Est propriétaire du cours et peut y administrer des utilisateurs';
$string['user_delegation:owncoursecat'] = 'Est propriétaire de la catégorie de cours et peut y administrer des utilisateurs';
$string['user_delegation:view'] = 'Peut voir le bloc';

$string['addnewgroup'] = 'Ajouter un nouveau groupe....';
$string['addbulkusers'] = 'Ajouter un lot d\'utilisateurs';
$string['attachtome'] = 'M\\\'attacher cet utilisateur'; // Js surescaping.
$string['backtocourse'] = 'Revenir au cours'; 
$string['backtohome'] = 'Revenir à la page d\'accueil';
$string['badblockid'] = 'ID de bloc invalide';
$string['blockname'] = 'Administration déléguée des utilisateurs';
$string['changeenrolment'] = 'Modifier les inscriptions';
$string['colon'] = ':';
$string['comma'] = ',';
$string['configallowenrol'] = 'Autoriser les inscriptions par la délégation';
$string['configenrolduration'] = 'Durée d\'inscription (en jours, vide pour pas de limite)';
$string['configcsvseparator'] = 'Séparateur CSV';
$string['configcsvseparator_desc'] = 'Caractère séparateur des champs dans le fichier CSV';
$string['configdelegationownerrole'] = 'Role pour la délégation';
$string['configdelegationownerrole_desc'] = 'Le rôle utilisé pour marquer la relation de délégation entre utilisateurs. Seuls les rôles avec la capacité "user_delegation:isbehaslfof" peuvent être utilisés.';
$string['configlastownerdeletes'] = 'Le dernier délégataire supprime';
$string['configlastownerdeletes_desc'] = 'Si activé, le dernier délégataire d\'un utilisateur le supprime de Moodle complètement, lorsque qu\'il le résilie de sa gestion locale.';
$string['configuseadvancedform'] = 'Formulaire utilisateur avancé';
$string['configuseadvancedform_desc'] = 'Le formulaire avancé permet de configurer beaucoup plus d\'options ';
$string['configuseuserquota'] = 'Utiliser les quotas utilisateur';
$string['configuseuserquota_desc'] = 'Si le plugin local "Limiteur de ressources" est installé, alors appliquer la limitation de quota utilisateur aux délégataires.';
$string['courseowner'] = 'Délégataire';
$string['courseownerdescription'] = 'Est délégataire de l\'objet. Il gère localement des utilisateurs (délégués) qu\'il peut inscrire et gérer dans ses cours (délégués).';
$string['coursetoassign'] = 'Inscrire à un cours&ensp;';
$string['createpassword'] = 'Password handling';
$string['doseol'] = 'Fins de lignes DOS';
$string['duplicatemails'] = 'Courriels en doublons';
$string['edituser'] = 'Modification d\'un utilisateur';
$string['edituseradvanced'] = 'Modification d\'un compte utilisateur (avancé)';
$string['emulatecommunity'] = 'Emuler la version communautaire';
$string['emulatecommunity_desc'] = 'Si elle est activée, cette option force le composant à fonctionner en version communautaire. Certaines fonctionnalités ne seront plus disponibles.';
$string['enrolnotallowed'] = 'Vous n\'avez pas les autorisations pour gérer les inscriptions';
$string['errornosuchuser'] = 'Cet utilisateur n\'existe pas';
$string['errors'] = 'Erreurs&ensp;';
$string['fakemail'] = 'Courriel de masquage';
$string['fakemaildomain'] = 'Domaine de remplacement (NOMAIL)';
$string['fakemails'] = 'Courriels masqués';
$string['fieldseparator'] = 'Séparateur de champ';
$string['filencoding'] = 'Encodage du fichier';
$string['filterconfirmed_all'] = 'Tous les utilisateurs';
$string['filterconfirmed_confirmedonly'] = 'Seulement les confirmés';
$string['filterconfirmed_unconformedonly'] = 'Seulement les non confirmés';
$string['groupadded'] = 'Membre ajouté au groupe';
$string['groupcreated'] = 'Groupe {$a} créé';
$string['grouptoassign'] = 'Nom du groupe à créer';
$string['importusers'] = 'Importer mes utilisateurs';
$string['inputfile'] = 'Fichier source';
$string['institution'] = 'Institution';
$string['invalidfieldname_areyousure'] = 'Nom de champ invalide {$a}';
$string['invalidmails'] = 'Courriels invalides';
$string['lastownerdeletes'] = 'Le dernier propriétaire supprime';
$string['licenseprovider'] = 'Fournisseur version Pro';
$string['licenseprovider_desc'] = 'Entrez la clef de votre distributeur.';
$string['licensekey'] = 'Clef de license pro';
$string['licensekey_desc'] = 'Entrez ici la clef de produit que vous avez reçu de votre distributeur.';
$string['linenumber'] = 'Ligne ';
$string['linenumber'] = 'Ligne {$a}';
$string['loadingcoursegroups'] = 'Chargement des groupes du cours...';
$string['loadinggroups'] = 'lecture des groupes...  veuillez patienter';
$string['maceol'] = 'Fins de lignes MAC';
$string['missingvalue'] = 'Valeur manquante pour le champ {$a->fieldname}';
$string['mnethostidnotexists'] = 'Cet identifiant d\'hôte Moodle n\'existe pas';
$string['mycourses'] = 'Mes cours';
$string['mydelegatedcourses'] = 'Mes cours administrés';
$string['myusers'] = 'Mes utilisateurs';
$string['new_user_delegation'] = 'Administrer mes utilisateurs';
$string['newgroupname'] = 'Groupe à créer avec les utilisateurs';
$string['newuser'] = 'Ajouter un utilisateur';
$string['newuseradded'] = 'Nouvel utilisateur ajouté';
$string['noassign'] = '-- Ajouter simplement l\'utilisateur --';
$string['assignto'] = 'Inscrire dans le cours {$a}';
$string['nogroups'] = 'Pas de groupes dans ce cours';
$string['nogroupswaitcourseslection'] = '-- Pas de cours sélectionné --';
$string['nomail'] = 'Utilisateurs sans adresse de courriel';
$string['nomail_help'] = 'Normalement dans Moodle les utilisateurs DEVRAIENT avoir une adresse de courriel valide. Si ce n\'est pas possible, mentionnez ce code dans la colonne "email" de votre fichier d\'import.';
$string['nomailplaceholder'] = 'Code de remplacement (NOMAIL)';
$string['noownedcourses'] = 'Je ne possède aucun cours en propre dans cette plate-forme.';
$string['noownedusers'] = 'Vous n\'êtes responsable d\'aucun utilisateur';
$string['nostudents'] = 'Il n\'y a aucun étudiant ici.';
$string['noteachers'] = 'Il n\'y a aucun enseignant.';
$string['onlyalphanum'] = 'Seulement alphanumérique';
$string['orcreate'] = ' ou créer le groupe ';
$string['pipe'] = '|';
$string['plugindist'] = 'Distribution du plugin';
$string['pluginname'] = 'Gérer mes utilisateurs';
$string['semicolon'] = ';';
$string['skipthisline'] = 'Sauter cette ligne';
$string['tab'] = 'TAB';
$string['teachers'] = 'Enseignants';
$string['totalcourses'] = 'Total';
$string['traininggroup'] = 'Groupe de formation';
$string['manager'] = 'Accompagnateur';
$string['trainee'] = 'Apprenant {no}';
$string['traineerow'] = 'Une liste d\'appenants définis par : Le prénom / le nom / l\'adresse mail (Seules les lignes complètement remplies sont traitées)';
$string['truncatefield'] = 'La valeur est trop longue pour ce champ';
$string['unassignedusers'] = 'Mes utilisateurs non assignés';
$string['unixeol'] = 'Fins de lignes UNIX';
$string['unmanaged'] = '(sans repsonsable)';
$string['uploadfile'] = 'Charger un fichier';
$string['uploadusers'] = 'Importer des utilisateurs';
$string['user_delegation'] = 'Gérer mes utilisateurs';
$string['useraccountupdated'] = 'Compte utilisateur modifié';
$string['useraccountupdated'] = 'Utilisateur mis à jour ';
$string['userbulkcreated'] = 'Compte utilisateur {$a->username} créé pour {$a->firstname} {$a->lastname}.';
$string['userbulkexists'] = 'L\'utilisateur {$a->firstname} {$a->lastname} existe avec l\'identifiant {$a->username}.';
$string['useradded'] = 'Utilisateur ajouté à vos utilisateurs.';
$string['userenrolled'] = 'Utilisateur inscrit dans le cours {$a}';
$string['userexists'] = 'L\\\'utilisateur existe déjà'; // js surescaping
$string['usermanagementoptions'] = 'Options de gestion des utilisateurs';
$string['username'] = 'Utilisateur : {$a} ';
$string['usernotaddederror'] = 'L\'utilisateur n\'a pas pu être créé';
$string['usernotaddedregistered'] = 'L\'utilisateur n\'a pas pu vous être ajouté';
$string['usernotupdatederror'] = 'L\'utilisateur n\'a pas pu être mis à jour';
$string['usersupdated'] = 'Utilisateurs mis à jour ';
$string['uservalid'] = 'Utilisateur valide. Redirection...';
$string['validatinguser'] = 'Utilisateur en cours.... Patientez';
$string['viewmycourses'] = 'Voir mes cours';
$string['viewmyusers'] = 'Voir mes utilisateurs';

$string['uploadusers_help'] = '

<p>Pour importer manuellement vos comptes utilisateurs à partir d\'un fichier texte, ce fichier doit être formaté de la façon suivante :</p>

<ul>
<li>Le fichier doit être encodé en UTF-8 (défaut) ou ISO 8859-1 (latin1).</li>
<li>La première ligne porte les noms de champs, en minuscule, sans espaces, dans le même ordre que les données</li>
<li>Chaque ligne du fichier contient un enregistrement.</li>
<li>Les données de chaque enregistrement sont séparées par un point-virgule (défaut) (ou un autre caractère de séparation).</li>
<li>Le fichier admet des lignes vides ou commentées par #, mais ne répétez pas la ligne de noms de champs.</li>
</ul>

<p><b>Champs requis</b> : ces champs doivent être mentionnés dans le premier enregistrement et définis pour tous les utilisateurs</p>

<p><code>username</code>, <code>firstname</code>, <code>lastname</code>, <code>email</code> et <code>password</code> si les mots de passe sont fournis par le fichier<br/>
Le champ "email" peut contenir le code d\'absence d\'adresse de courriel (NOMAIL par défaut).</p>

<p><b>Champs optionnels</b> : tous ces champs sont optionnels. Les valeurs éventuellement précisées dans le fichier seront utilisées. Sinon, c\'est la valeur par défaut pour ce champ qui sera utilisée.</p>

<p><code>city</code> pour la ville (en majuscules), <code>institution</code>, <code>department</code>, <code>country</code> (FR, UK), <code>lang>/code> (fr,en,es,...) <!-- auth, ajax, timezone, idnumber, icq, --> phone1, phone2, address, url, description, <!-- mailformat, maildisplay, htmleditor, autosubscribe, emailstop --></p>

<p><b>Champs de profil personnalisés</b> : optionnel, xxxxx doit être remplacé par le nom abrégé du champ personnalisé. L\'administrateur de Moodle devrait avoir publié à votre intention des instructions concernant 
les champs supplémentaires supportés par cette plate-forme.</p>

    <code>profile_field_xxxxx</code>

<p><b>Champs spéciaux</b> : Vous pouvez supprimer ou suspendre des utilisateurs grâce aux champs ci-dessous. Notez que vous ne pouvez pas suspendre
ni supprimer un utilisateur partagé avec au moins un autre tuteur.</p>

    <code>deleted, suspended</code>

<p><b>Champs d\'inscription</b> : Ce mode d\'importation ne support ni les champs d\'inscription ni les champs relatifs aux roles (inscription étudiante par défaut).</p>

<p>Pour les données booléennes, utilisez 0 pour faux et 1 pour vrai.</p>
';

$string['createpassword_help'] = '
Si vous laissez Moodle générer les mots de passe, vous n\'aurez aucune connaissance de ceux-ci.
Les mots de passe sont envoyés automatiquement aux utilisateur sur leur adresse de courriel déclarée dans le fichier. Elle doit donc être valide.
';

$string['plugindist_desc'] = '<p>Ce plugin est distribué dans la communauté Moodle pour l\'évaluation de ses fonctions centrales
correspondant à une utilisation courante du plugin. Une version "professionnelle" de ce plugin existe et est distribuée
sous certaines conditions, afin de soutenir l\'effort de développement, amélioration; documentation et suivi des versions.</p>
<p>Contactez un distributeur pour obtenir la version "Pro" et son support.</p>
<ul><li><a href="http://www.activeprolearn.com/plugin.php?plugin=block_use_stats&lang=fr">ActiveProLearn SAS</a></li>
<li><a href="http://www.edunao.com">Edunao SAS</a></li></ul>';
