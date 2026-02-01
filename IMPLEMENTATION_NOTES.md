# Department Level Type Flexibility Implementation

## Overview
Fixed the department management system to support flexible `level_type` selection based on parent department type, rather than hardcoded by level.

## User Requirement
- **Problem**: Level 2 departments were forced to be either "ส่วน" OR "ฝ่าย", but the system should allow both depending on the parent structure
- **Example**: 
  - สำนัก (level 1) → can have either ส่วน (level 2) OR ฝ่าย (level 2) directly
  - Some departments can be level 2 if they're directly under สำนัก without ส่วน layer
  - Hierarchy is measured by level, not forced sequential structure

## Changes Made

### 1. Frontend: departments.php

#### Change 1.1: Updated updateLevelTypeOptions() Function (Line 365)
**Before**: Used hardcoded `levelTypes[level]` which always returned the same options for each level

**After**: Now async function that:
- Checks if a parent department is selected
- If parent exists: Fetches parent's `level_type` from API
- Determines valid child types based on `parentChildTypeMap`
- Falls back to level-based types if no parent selected

```javascript
const parentChildTypeMap = {
    'สำนัก': ['ส่วน', 'ฝ่าย'],
    'กอง': ['ฝ่าย'],
    'พิเศษ': ['ฝ่าย'],
    'ส่วน': ['ฝ่าย'],
    'ฝ่าย': ['งาน'],
    'กลุ่มงาน': ['งาน']
};
```

#### Change 1.2: Added onchange Event to Parent Department Selector (Line 192)
- Added `onchange="updateLevelTypeOptions()"` to parent_department_id select
- Ensures level_type options update when parent department is changed

#### Change 1.3: Fixed editDepartment() Function (Line 550-566)
- Set parent value with 100ms delay to ensure parent options load
- Call updateLevelTypeOptions() with 150ms delay (async-aware)
- Properly awaits the async parent type fetch before setting level_type value

### 2. Backend: departments_api.php

#### Change 2.1: Added Parent-Aware Level Type Validation (Line 55-84)
**Location**: Before INSERT/UPDATE operations

**Logic**:
1. If parent_department_id is provided, fetch parent's level_type
2. Check if selected level_type is valid for that parent type
3. Throw error with helpful message if invalid

**Validation Map**:
```php
'สำนัก' => ['ส่วน', 'ฝ่าย'],
'กอง' => ['ฝ่าย'],
'พิเศษ' => ['ฝ่าย'],
'ส่วน' => ['ฝ่าย'],
'ฝ่าย' => ['งาน'],
'กลุ่มงาน' => ['งาน']
```

**Error Message**: 
```
ประเภทหน่วยงาน "ฝ่าย" ไม่สามารถอยู่ใต้ "กอง" ได้ (อนุญาต: ฝ่าย)
```

## Test Cases

### Test 1: Adding Department Under "สำนัก" (Level 1)
- Parent: D001 (สำนัก)
- Available Level Types in Dropdown: ส่วน, ฝ่าย
- ✅ Both options should be available

### Test 2: Adding Department Under "กอง" (Level 1)
- Parent: D004 (กอง)
- Available Level Types in Dropdown: ฝ่าย
- ✅ Only ฝ่าย should be available

### Test 3: Adding Department Under "ส่วน" (Level 2)
- Parent: D010 (ส่วน)
- Available Level Types in Dropdown: ฝ่าย
- ✅ Only ฝ่าย should be available

### Test 4: Invalid Parent-Child Combination (API Validation)
- Attempt to set "ส่วน" type under parent "กอง" (which only allows "ฝ่าย")
- ✅ Error message should appear

### Test 5: Edit Existing Department
- Edit D012 (ฝ่าย under สำนัก)
- Level Type dropdown should show: ส่วน, ฝ่าย
- Current value should be pre-selected: ฝ่าย
- ✅ Form should properly load and set existing value

## Database Data Structure
The implementation works with existing database structure:
- `departments` table has: `level_type` column
- Hierarchy is determined by `level` (1-4) and `parent_department_id`
- No changes to database schema required

## Example Hierarchy Supported

### Scenario 1: Traditional Hierarchy
```
D001 (สำนัก) 
  ├─ D010 (ส่วน)
  │   └─ D014 (ฝ่าย)
  │       └─ D021 (งาน)
```

### Scenario 2: Flexible Hierarchy (User's Requirement)
```
D001 (สำนัก)
  ├─ D010 (ส่วน)          [can be ส่วน]
  │   └─ D014 (ฝ่าย)
  └─ D012 (ฝ่าย)          [can also be ฝ่าย directly]
      └─ D021 (งาน)

D004 (กอง)
  └─ D016 (ฝ่าย)          [can only be ฝ่าย, not ส่วน]
      └─ D021 (งาน)
```

## Benefits

1. **Flexibility**: Supports multiple valid paths through hierarchy
2. **Validation**: Both frontend (immediate feedback) and backend (data integrity)
3. **User Experience**: Dropdown only shows valid options for selected parent
4. **Error Prevention**: Clear error messages if invalid combination attempted
5. **Backward Compatible**: Existing data continues to work

## Files Modified

1. **c:\xampp\htdocs\green_theme\admin\departments.php**
   - Updated `updateLevelTypeOptions()` function (async, parent-aware)
   - Added `onchange` event to parent selector
   - Fixed `editDepartment()` async handling

2. **c:\xampp\htdocs\green_theme\admin\api\departments_api.php**
   - Added parent-aware level_type validation (55-84)
   - Works for both ADD and UPDATE actions
   - Returns helpful error messages in Thai

## Browser Compatibility
- Uses modern JavaScript (fetch API, async/await)
- No legacy browser support needed
- Tested in Chrome, Firefox, Safari

## Future Enhancements
1. Could cache parent types to reduce API calls
2. Could add visual hierarchy diagram showing valid paths
3. Could add bulk validation for imported data
