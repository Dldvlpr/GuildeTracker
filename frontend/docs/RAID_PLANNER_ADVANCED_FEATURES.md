# Raid Assignment Planner - Advanced Features Documentation

**Date**: 2025-11-16
**Version**: 2.0 - Production Ready
**Status**: ✅ Phase 3 Complete

---

## 🎯 Overview

This document covers the **4 advanced features** added to complete the Raid Assignment Planner's Phase 3 development:

1. **Visual Resize Handles** - Drag-to-resize blocks
2. **Grid Overlay** - Visualize the 12-column grid
3. **Collision Detection** - Warn about overlapping blocks
4. **Block Templates** - Save and reuse block configurations

These features transform the planner into a **professional-grade page builder** comparable to Figma or Canva.

---

## 1. Visual Resize Handles 🎨

### Description
Interactive handles appear on selected blocks, allowing users to resize by dragging edges.

### Implementation

**Location**: `frontend/src/components/RaidPlanCanvas.vue`

**Key Functions**:
```typescript
function onResizeLeft(block: RaidPlanBlock, e: MouseEvent) {
  resizing.value = true;
  showGridOverlay.value = true;
  // Calculate column changes based on mouse movement
  // Update block.colStart and block.colSpan
}

function onResizeRight(block: RaidPlanBlock, e: MouseEvent) {
  resizing.value = true;
  showGridOverlay.value = true;
  // Calculate column changes based on mouse movement
  // Update block.colSpan only
}
```

**Visual Elements**:
- Left handle: Shows when `block.colStart > 1`
- Right handle: Shows when `block.colStart + block.colSpan - 1 < 12`
- Emerald green color on hover
- Smooth transitions

**UX Details**:
- Cursor changes to `ew-resize` (↔)
- Handles only visible on selected blocks
- Auto-snaps to allowed spans (2, 3, 4, 6, 8, 12)
- Prevents invalid sizes (min 2 cols, max 12 cols)

**Code Snippet**:
```vue
<!-- Resize Handles in template -->
<div
  v-if="selectedBlockId === block.id && block.colStart > 1"
  class="absolute left-0 top-0 bottom-0 w-2 cursor-ew-resize hover:bg-emerald-500/30"
  @mousedown="onResizeLeft(block, $event)"
>
  <div class="w-1 h-8 bg-emerald-500/60 rounded-full opacity-0 group-hover/handle:opacity-100"></div>
</div>
```

**Keyboard Alternative**: Arrow keys (←/→) still work for precise control.

---

## 2. Grid Overlay 📐

### Description
A visual 12-column grid appears when resizing or selecting blocks, helping users align and size elements precisely.

### Implementation

**Location**: `frontend/src/components/RaidPlanCanvas.vue`

**State Management**:
```typescript
const showGridOverlay = ref(false);

function selectBlock(id: string) {
  selectedBlockId.value = id;
  showGridOverlay.value = true;
  // Auto-hide after 2 seconds
  setTimeout(() => {
    if (!resizing.value) showGridOverlay.value = false;
  }, 2000);
}
```

**Visual Design**:
```vue
<div
  v-if="showGridOverlay && innerBlocks.length"
  class="absolute inset-4 pointer-events-none z-0 grid grid-cols-12 gap-3"
>
  <div v-for="i in 12" :key="i" class="border-l border-r border-dashed border-emerald-500/20">
    <span class="absolute top-0 left-1/2 -translate-x-1/2 text-[10px] text-emerald-400/60 font-mono">
      {{ i }}
    </span>
  </div>
</div>
```

**Behavior**:
- Appears when:
  - Block is selected
  - Resize handles are being dragged
- Auto-hides after 2 seconds (unless actively resizing)
- Dashed emerald borders with column numbers
- Positioned behind blocks (`z-0` vs `z-10`)

**UX Benefits**:
- Helps users understand grid structure
- Makes alignment easier
- Provides visual feedback during layout changes
- Non-intrusive (auto-hides)

---

## 3. Collision Detection ⚠️

### Description
Automatically detects when blocks overlap on the same row and displays visual warnings.

### Implementation

**Location**: `frontend/src/components/RaidPlanCanvas.vue`

**Detection Algorithm**:
```typescript
function detectCollisions(blocks: RaidPlanBlock[]): Map<string, string[]> {
  const collisions = new Map<string, string[]>();

  for (let i = 0; i < blocks.length; i++) {
    const blockA = blocks[i];
    const rowA = (blockA as any).row || 0;
    if (rowA === 0) continue; // Skip auto-flow blocks

    for (let j = i + 1; j < blocks.length; j++) {
      const blockB = blocks[j];
      const rowB = (blockB as any).row || 0;
      if (rowB === 0) continue;

      // Check if same row
      if (rowA !== rowB) continue;

      // Check column overlap
      const aStart = blockA.colStart;
      const aEnd = blockA.colStart + blockA.colSpan - 1;
      const bStart = blockB.colStart;
      const bEnd = blockB.colStart + blockB.colSpan - 1;

      if (!(aEnd < bStart || bEnd < aStart)) {
        // Collision detected!
        if (!collisions.has(blockA.id)) collisions.set(blockA.id, []);
        if (!collisions.has(blockB.id)) collisions.set(blockB.id, []);
        collisions.get(blockA.id)!.push(blockB.id);
        collisions.get(blockB.id)!.push(blockA.id);
      }
    }
  }

  return collisions;
}

const collisions = computed(() => detectCollisions(innerBlocks.value));
```

**Visual Indicators**:

1. **Global Banner** (top center):
```vue
<div
  v-if="collisions.size > 0"
  class="absolute top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 bg-red-500/90 text-white rounded-lg animate-pulse"
>
  <span>⚠️ {{ collisions.size }} block(s) overlapping! Check manual row positions.</span>
</div>
```

2. **Per-Block Badge**:
```vue
<div
  v-if="collisions.has(block.id)"
  class="absolute -top-2 -right-2 px-2 py-1 bg-red-500 text-white text-[10px] font-bold rounded-full"
>
  ⚠️ Collision
</div>
```

3. **Border Highlight**:
```vue
:class="[
  collisions.has(block.id)
    ? 'ring-2 ring-red-500 shadow-red-500/20 animate-pulse'
    : /* normal styles */
]"
```

**Important Notes**:
- Only detects collisions for blocks with **manual row numbers** (`row > 0`)
- Auto-flow blocks (`row = 0`) never collide (CSS handles layout)
- Collision detection is **reactive** (updates instantly)

**Why This Matters**:
Users who manually set row positions might accidentally overlap blocks. This feature prevents visual bugs and data loss.

---

## 4. Block Templates System 💾

### Description
Save configured blocks as reusable templates and load them later.

### Implementation

**Location**: `frontend/src/views/RaidAssignments.vue`

**Data Structure**:
```typescript
type BlockTemplate = {
  id: string;
  name: string;
  description?: string;
  block: RaidPlanBlock;  // Full block config (type, data, colSpan, etc.)
  createdAt: number;
};
```

**Storage**: LocalStorage key `raidPlan:blockTemplates` (max 50 templates)

**Key Functions**:

1. **Save Template**:
```typescript
function saveBlockAsTemplate() {
  // Check if block is selected
  if (!canvasRef.value?.selectedBlockId) {
    alert('Please select a block first');
    return;
  }

  // Open modal to name template
  showSaveTemplate.value = true;
}

function confirmSaveTemplate() {
  const block = blocks.value.find(b => b.id === selectedBlockId);
  const newTemplate: BlockTemplate = {
    id: String(Date.now()),
    name: templateName.value.trim(),
    block: JSON.parse(JSON.stringify(block)), // Deep clone
    createdAt: Date.now(),
  };

  // Save to localStorage
  const templates = getBlockTemplates();
  templates.unshift(newTemplate);
  localStorage.setItem(TEMPLATES_KEY, JSON.stringify(templates.slice(0, 50)));
}
```

2. **Load Template**:
```typescript
function loadBlockTemplate(template: BlockTemplate) {
  const newBlock = JSON.parse(JSON.stringify(template.block));
  newBlock.id = String(nextId++);  // New unique ID
  newBlock.colStart = 1;           // Reset position
  newBlock.row = 0;                // Auto-flow
  blocks.value.push(newBlock);
}
```

3. **Delete Template**:
```typescript
function deleteBlockTemplate(templateId: string) {
  const templates = getBlockTemplates().filter(t => t.id !== templateId);
  localStorage.setItem(TEMPLATES_KEY, JSON.stringify(templates));
}
```

**UI Elements**:

1. **Save Button** (Header):
```vue
<button @click="saveBlockAsTemplate">
  💾 Save Block
</button>
```

2. **Save Modal**:
```vue
<BaseModal v-model="showSaveTemplate" title="Save Block as Template">
  <input v-model="templateName" placeholder="e.g., My Cooldown Rotation Setup" />
</BaseModal>
```

3. **Templates List** (Sidebar):
```vue
<section v-if="blockTemplates.length">
  <h3>💾 Saved Templates</h3>
  <div v-for="tpl in blockTemplates" :key="tpl.id">
    <button @click="loadBlockTemplate(tpl)">{{ tpl.name }}</button>
    <button @click="deleteBlockTemplate(tpl.id)">✕</button>
  </div>
</section>
```

**Use Cases**:
- Save complex Cooldown Rotation setups
- Reuse Boss Grid configurations
- Share templates between raid plans
- Build a library of common patterns

**Data Preserved**:
- Block type (COOLDOWN_ROTATION, BOSS_GRID, etc.)
- All block data (columns, rows, assignments, etc.)
- Title and settings

**Data Reset on Load**:
- ID (new unique ID)
- Position (starts at column 1)
- Row (auto-flow)

---

## 📊 Feature Comparison

| Feature | Before | After |
|---------|--------|-------|
| **Resize** | Manual input fields only | Drag handles + inputs |
| **Grid Visibility** | Invisible, users guessed | Visual overlay on demand |
| **Collision Detection** | No warnings | Real-time alerts + highlights |
| **Reusability** | Copy-paste manual | Save/load templates |

---

## 🎨 Visual Design Language

### Color Palette
- **Emerald (#10b981)**: Interactive elements (resize handles, grid, success)
- **Red (#ef4444)**: Warnings, errors, collisions
- **Slate (#64748b)**: Neutral, inactive states
- **Blue (#3b82f6)**: Informational (Help modal)

### Animations
- **Pulse**: Collision warnings (`animate-pulse`)
- **Opacity Fade**: Grid overlay (300ms transition)
- **Scale**: Selected blocks (1.01x)
- **Hover**: Resize handles (opacity 0 → 100)

### Z-Index Layers
```
z-50: Collision banner
z-20: Collision badges
z-10: Draggable blocks
z-0:  Grid overlay
```

---

## 🧪 Testing Checklist

### Resize Handles
- [x] Handles appear when block is selected
- [x] Left handle only shows if `colStart > 1`
- [x] Right handle only shows if block can expand
- [x] Dragging updates colSpan in real-time
- [x] Auto-snaps to allowed spans (2, 3, 4, 6, 8, 12)
- [x] Prevents invalid sizes (too small or exceeding grid)

### Grid Overlay
- [x] Appears when block is selected
- [x] Shows when resizing
- [x] Auto-hides after 2 seconds
- [x] Column numbers display correctly (1-12)
- [x] Positioned behind blocks
- [x] Smooth fade in/out

### Collision Detection
- [x] Detects row-based collisions correctly
- [x] Ignores auto-flow blocks (row = 0)
- [x] Shows global banner with count
- [x] Shows per-block badges
- [x] Red borders + pulse animation
- [x] Updates reactively as blocks move

### Block Templates
- [x] Save button only works when block is selected
- [x] Modal prompts for template name
- [x] Template saves to localStorage
- [x] Templates appear in sidebar
- [x] Loading creates new block with fresh ID
- [x] Deleting removes from localStorage
- [x] Max 50 templates enforced

---

## 🚀 Performance Considerations

### Collision Detection
- **Complexity**: O(n²) where n = number of blocks with manual rows
- **Optimization**: Only checks blocks with `row > 0`
- **Impact**: Negligible for typical use (<100 blocks)

### Grid Overlay
- **Cost**: 12 DOM elements (one per column)
- **Optimization**: Conditionally rendered (`v-if`)
- **Impact**: Minimal (static elements, no animations)

### Templates
- **Storage**: LocalStorage (max ~5MB)
- **Limit**: 50 templates to prevent bloat
- **Serialization**: `JSON.stringify` on save/load

### Resize Handles
- **Events**: `mousemove` and `mouseup` listeners
- **Cleanup**: Removed on `mouseup` (no memory leaks)
- **Throttling**: Not needed (fast DOM updates)

---

## 📝 User Workflow Examples

### Example 1: Creating a Reusable Cooldown Block

1. Add "Cooldown Rotation" from sidebar
2. Configure columns (P1 0:30, P1 1:30, P2 0:30)
3. Configure rows (Barrier, AM, Rally)
4. Assign characters to cells
5. Click "💾 Save Block"
6. Name it "Standard 3-Phase CD Rotation"
7. **Result**: Template saved, reusable in other raid plans

### Example 2: Fixing Overlapping Blocks

1. User sets two blocks to `row = 2`
2. Blocks overlap columns 1-6 and 4-9
3. **System response**:
   - Red banner appears: "⚠️ 2 blocks overlapping!"
   - Both blocks pulse with red borders
   - Badges show "⚠️ Collision"
4. User adjusts one block to `row = 3`
5. **Result**: Warnings disappear instantly

### Example 3: Precise Layout with Grid

1. User selects a block
2. Grid overlay appears showing 12 columns
3. User drags right resize handle
4. Grid helps visualize exact column alignment
5. Block snaps to 6 columns (exactly half)
6. **Result**: Perfect 2-column layout

---

## 🔗 Related Files

**Modified Files**:
- `frontend/src/components/RaidPlanCanvas.vue` - All 4 features
- `frontend/src/views/RaidAssignments.vue` - Templates UI + modal

**Documentation**:
- `RAID_PLANNER_IMPROVEMENTS.md` - Phase 3.1 improvements (UX/Grid fixes)
- `RAID_PLANNER_ADVANCED_FEATURES.md` - **This file** (Phase 3.2 features)

**Interface**:
- `frontend/src/interfaces/raidPlan.interface.ts` - Type definitions (unchanged)

---

## 🎓 Learning & Best Practices

### Vue 3 Composition API
- Used `ref()` for local state
- Used `computed()` for reactive collision detection
- Proper cleanup in `onUnmounted()` hooks

### TypeScript Patterns
- Defined `BlockTemplate` type explicitly
- Type guards for block data access
- Proper event typing (`MouseEvent`, `DragEvent`)

### LocalStorage Best Practices
- Namespaced keys (`raidPlan:blockTemplates`)
- Max size enforcement (50 templates)
- Try-catch for JSON parsing
- Deep cloning to avoid mutations

### UX Patterns
- Auto-hide timers for overlays
- Visual feedback on all interactions
- Progressive disclosure (handles only when selected)
- Clear error states (red = danger)

---

## 🏆 Phase 3 Completion Status

**Epic 3.2 - Page Builder with 12-Column Grid**: ✅ **100% Complete**

| Task | Status |
|------|--------|
| 12-column grid system | ✅ Complete |
| Smart block sizing | ✅ Complete |
| Visual feedback (selection, drag) | ✅ Complete |
| Keyboard shortcuts | ✅ Complete |
| Help panel | ✅ Complete |
| Empty state | ✅ Complete |
| **Resize handles** | ✅ **Complete** |
| **Grid overlay** | ✅ **Complete** |
| **Collision detection** | ✅ **Complete** |
| **Block templates** | ✅ **Complete** |

---

## 🎯 Next Steps (Future Enhancements)

These features were NOT implemented but are suggested for future iterations:

1. **Backend Template Sync**
   - Store templates in database
   - Share templates across users/guilds
   - Template marketplace/gallery

2. **Advanced Collision Resolution**
   - Auto-suggest alternative positions
   - "Fix overlaps" button (auto-reflow)
   - Collision preview before drop

3. **Enhanced Grid Tools**
   - Rulers with measurements
   - Snap-to-grid toggle
   - Grid density settings (6, 12, 24 columns)

4. **Template Enhancements**
   - Template categories/tags
   - Search/filter templates
   - Import/export as JSON files
   - Template preview thumbnails

5. **Touch/Mobile Support**
   - Touch-friendly resize handles
   - Mobile-optimized grid overlay
   - Gesture-based interactions

---

**Status**: Production Ready ✅
**Epic**: Phase 3.2 - Advanced Page Builder
**Impact**: Killer feature complete, ready to differentiate from competitors
