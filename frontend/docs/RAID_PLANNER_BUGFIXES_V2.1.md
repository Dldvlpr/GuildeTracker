# Raid Assignment Planner - Bug Fixes & New Features v2.1

**Date**: 2025-11-16
**Version**: 2.1 - Bug Fixes + Image Support
**Status**: ✅ Production Ready

---

## 🐛 Bugs Corrigés

### 1. **Help Modal Scroll Issue** ✅

**Problème**: Le modal d'aide ne scrollait pas lorsque le contenu dépassait la hauteur de l'écran.

**Cause**: Le body du modal (`BaseModal.vue`) n'avait pas de `max-height` ni `overflow-y-auto`.

**Solution**:
```vue
<!-- Avant -->
<div class="px-6 py-2">
  <slot />
</div>

<!-- Après -->
<div class="px-6 py-2 max-h-[60vh] overflow-y-auto">
  <slot />
</div>
```

**Impact**: Tous les modals (Help, History, Templates) peuvent maintenant afficher du contenu long avec scroll.

**Fichier**: `frontend/src/components/ui/BaseModal.vue:55`

---

### 2. **Boss Grid Layout Design Bugs** ✅

**Problèmes identifiés**:
- Grid responsive cassée sur petits écrans (3 colonnes → trop serré)
- Espacement incohérent
- Bouton "Add position" mal aligné
- Cards de positions trop petites
- Texte tronqué

**Solutions appliquées**:

#### A. Grid Responsive Améliorée
```vue
<!-- Avant: 3 colonnes sur large, cassait le design -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">

<!-- Après: 2 colonnes max, meilleur pour lecture -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
```

#### B. Header du Block
```vue
<!-- Avant -->
<div class="flex items-center justify-between">
  <div class="text-[11px] text-slate-400">Configure positions...</div>
  <button>+ Add position</button>
</div>

<!-- Après: Stack sur mobile, meilleur espacement -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
  <div class="text-[11px] text-slate-400">Configure positions...</div>
  <button class="self-start">+ Add position</button>
</div>
```

#### C. Cards de Positions Améliorées
```vue
<!-- Avant: p-2, petits inputs -->
<div class="rounded-md border border-slate-700 bg-slate-900/60 p-2">
  <input class="text-[11px]" />
</div>

<!-- Après: p-3, inputs plus lisibles, hover effect -->
<div class="rounded-lg border border-slate-700 bg-slate-900/60 p-3 transition-all hover:border-slate-600">
  <input class="text-xs font-medium" placeholder="Position name" />
</div>
```

**Améliorations visuelles**:
- Padding augmenté (2 → 3)
- Border radius (md → lg)
- Hover effect sur les cards
- Font-size inputs (11px → 12px)
- Placeholder ajouté
- Gap augmenté (2 → 3)
- Espacement header (mb-1 → mb-2)

**Fichier**: `frontend/src/components/RaidPlanCanvas.vue:1079-1125`

---

## ✨ Nouvelles Fonctionnalités

### 3. **IMAGE Block Type** 🖼️ ✅

**Description**: Nouveau type de block permettant d'ajouter des images aux raid plans (screenshots de boss, maps, ability icons, stratégies visuelles).

**Cas d'usage**:
- Screenshots de boss mechanics
- Maps de positionnement
- Icônes d'abilities à interrupt
- Diagrammes de stratégie
- Références visuelles

**Implémentation**:

#### A. Nouveau Type
```typescript
// raidPlan.interface.ts
export type BlockType =
  | ... // existing types
  | 'IMAGE'; // ← NEW

export interface RaidPlanBlock extends RaidPlanBlockBase {
  data?: {
    // IMAGE (NEW)
    imageUrl?: string;
    imageCaption?: string;
    imageSize?: 'contain' | 'cover' | 'fill';
    // ... other types
  };
}
```

#### B. Default Settings
```typescript
// RaidAssignments.vue
case 'IMAGE':
  base.title = 'Image';
  base.colSpan = 6; // Half width by default
  base.data = {
    imageUrl: '',
    imageCaption: '',
    imageSize: 'contain',
  };
  break;
```

#### C. UI Features

**1. Image URL Input**:
- Paste direct URL (imgur, Discord CDN, etc.)
- Helper text explaining where to upload images
- Real-time preview

**2. Caption Field**:
- Optional caption pour décrire l'image
- Displayed under image in italic

**3. Display Mode Selector**:
- **Contain**: Fit entire image (maintains aspect ratio)
- **Cover**: Fill container (may crop)
- **Fill**: Stretch to fill (may distort)

**4. Live Preview**:
- Shows image in 192px height preview
- Error handling avec fallback SVG
- Caption displayed below

**5. Empty State**:
- Dashed border placeholder
- 🖼️ Emoji icon
- Instructions claires

**Code Snippet**:
```vue
<div v-if="block.type === 'IMAGE'" class="space-y-2">
  <!-- URL Input -->
  <input
    type="text"
    :value="block.data?.imageUrl ?? ''"
    @input="updateBlockData(block, { imageUrl: $event.target.value })"
    placeholder="https://example.com/image.png"
  />

  <!-- Caption Input -->
  <input
    type="text"
    :value="block.data?.imageCaption ?? ''"
    @input="updateBlockData(block, { imageCaption: $event.target.value })"
    placeholder="e.g., Boss positioning for Phase 2"
  />

  <!-- Display Mode -->
  <select :value="block.data?.imageSize ?? 'contain'">
    <option value="contain">Fit (contain)</option>
    <option value="cover">Fill (cover)</option>
    <option value="fill">Stretch (fill)</option>
  </select>

  <!-- Preview with error handling -->
  <div v-if="block.data?.imageUrl" class="h-48 bg-slate-950 rounded">
    <img
      :src="block.data.imageUrl"
      :alt="block.data?.imageCaption"
      :style="{ objectFit: block.data?.imageSize || 'contain' }"
      @error="$event.target.src = 'data:image/svg+xml,...fallback...'"
    />
    <div v-if="block.data?.imageCaption" class="text-center italic">
      {{ block.data.imageCaption }}
    </div>
  </div>

  <!-- Empty state -->
  <div v-else class="border-dashed p-6 text-center">
    <div class="text-2xl">🖼️</div>
    <div>Paste an image URL above to display</div>
  </div>
</div>
```

**Sidebar Button**:
```vue
<button @click="add('IMAGE')">
  🖼️ Image
</button>
```

**Default Size**: 6 columns (half-width) - ideal for side-by-side image + notes

**Fichiers modifiés**:
- `frontend/src/interfaces/raidPlan.interface.ts` - Type definitions
- `frontend/src/components/BlockSidebar.vue` - Add button
- `frontend/src/views/RaidAssignments.vue` - Block creation logic
- `frontend/src/components/RaidPlanCanvas.vue` - Block rendering

---

## 📊 Comparaison Avant/Après

| Aspect | Avant | Après |
|--------|-------|-------|
| **Help Modal** | Pas de scroll → contenu caché | Scroll fluide avec max-h-60vh |
| **Boss Grid** | 3 cols sur large → serré | 2 cols max → lisible |
| **Boss Grid Cards** | Petites, texte 11px | Plus grandes, texte 12px, hover |
| **Image Support** | Aucun | Block IMAGE complet avec preview |

---

## 🎨 UX Improvements Summary

### Boss Grid
- ✅ Mobile-friendly (stack sur petit écran)
- ✅ Cards plus grandes et lisibles
- ✅ Hover effects
- ✅ Meilleur espacement
- ✅ Placeholder sur inputs

### Image Block
- ✅ Support URL d'images
- ✅ Preview en temps réel
- ✅ 3 modes d'affichage
- ✅ Captions optionnelles
- ✅ Error handling robuste
- ✅ Empty state clair

---

## 🧪 Testing Checklist

### Help Modal
- [x] Modal s'ouvre correctement
- [x] Contenu scroll avec wheel/touchpad
- [x] Scrollbar visible si contenu dépasse
- [x] Fonctionne sur tous les modals (Help, History, Templates)

### Boss Grid
- [x] Responsive 1 col mobile, 2 cols desktop
- [x] Header stack correctement sur mobile
- [x] Cards ont bon padding et hover
- [x] Inputs lisibles et avec placeholder
- [x] Drag & drop fonctionne toujours

### Image Block
- [x] Ajoute via sidebar "🖼️ Image"
- [x] URL input fonctionne
- [x] Caption input fonctionne
- [x] Display mode switcher fonctionne
- [x] Preview s'affiche avec URL valide
- [x] Fallback SVG si image 404
- [x] Empty state affiché sans URL
- [x] Caption s'affiche sous l'image

---

## 💡 Tips pour Utilisateurs

### Utiliser les Images

**Où uploader les images**:
1. **Imgur.com** (recommandé)
   - Gratuit, pas de compte requis
   - Upload → Copy direct link
   - Coller dans le champ Image URL

2. **Discord**
   - Upload image dans un channel
   - Right-click → Copy Link
   - Coller l'URL

3. **Gyazo / Lightshot**
   - Screenshot tools avec auto-upload
   - Génèrent des URLs directes

**Types d'images utiles**:
- 📸 Screenshots de boss mechanics
- 🗺️ Maps de positionnement (MS Paint works!)
- 🎯 Ability priority lists (screenshot)
- 📊 Diagrams de stratégie
- 🖼️ References visuelles (Wowhead, Icy Veins)

**Best Practices**:
- Utiliser des images claires et contrastées
- Ajouter des captions descriptives
- Mode "Contain" pour stratégies (garde aspect ratio)
- Mode "Cover" pour backgrounds
- Taille recommandée: 800-1200px width

---

## 🔗 Related Files

**Modified**:
- `frontend/src/components/ui/BaseModal.vue` - Scroll fix
- `frontend/src/components/RaidPlanCanvas.vue` - Boss Grid + IMAGE block
- `frontend/src/views/RaidAssignments.vue` - IMAGE block logic
- `frontend/src/interfaces/raidPlan.interface.ts` - IMAGE type
- `frontend/src/components/BlockSidebar.vue` - IMAGE button

**Documentation**:
- `RAID_PLANNER_IMPROVEMENTS.md` - Phase 3.1 (UX fixes)
- `RAID_PLANNER_ADVANCED_FEATURES.md` - Phase 3.2 (Advanced features)
- `RAID_PLANNER_BUGFIXES_V2.1.md` - **This file** (Bug fixes + Image)

---

## 🚀 Prochaines Étapes Suggérées

### Basé sur les Spreadsheets Réels

**Une fois que tu me décriras les spreadsheets**, je pourrai implémenter :

1. **Loot Priority Block** (si patterns dans les sheets)
   - Table avec Item | Priority | Notes
   - Drag & drop characters pour priority order

2. **Positioning Map Block** (si maps visuelles)
   - Canvas pour dessiner positions
   - Ou grid-based avec icons

3. **Better Cooldown Rotation**
   - Timelined (0:30, 1:00, etc.)
   - Color-coded par type (defensive, offensive)

4. **Assignment by Boss** (si multi-boss layout)
   - Tabs par boss
   - Copy assignments between bosses

**Peux-tu me décrire** ce que tu vois dans les Google Sheets que tu as linkés ?
- Quelles sections/tableaux ?
- Comment sont organisées les informations ?
- Y a-t-il des couleurs/highlights spécifiques ?
- Des patterns que tu aimerais reproduire ?

---

**Status**: v2.1 Complete ✅
**Bugs Fixed**: 2/2
**Features Added**: 1 (IMAGE block)
**Ready for**: User feedback on spreadsheet patterns
