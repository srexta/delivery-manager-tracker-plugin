# DMTP Sprint Shortcodes Usage Guide

## Overview

The DMTP plugin provides three specialized shortcodes for different sprint management needs:

- **`[dmtp_sprint_roadmap]`** - Comprehensive view of sprint data with full metrics
- **`[dmtp_sprint_marketing]`** - Focused view for marketing tasks and checklists
- **`[dmtp_sprint_documentation]`** - Focused view for documentation tasks and checklists

## Basic Usage

### Sprint Roadmap (Full View)
```
[dmtp_sprint_roadmap]
```
This will display all sprints in the default roadmap view with full metrics.

### Sprint Marketing 
```
[dmtp_sprint_marketing]
```
This will display sprints with focus on marketing tasks and their completion status.

### Sprint Documentation
```
[dmtp_sprint_documentation]
```
This will display sprints with focus on documentation tasks and their completion status.

## Shortcode Attributes

### Common Attributes (All Shortcodes)

| Attribute | Default | Description | Options |
|-----------|---------|-------------|---------|
| `team` | (all) | Filter sprints by team | Any team name configured in DMTP settings |
| `count` | `10` | Number of sprints to display | Any positive integer |
| `status` | `all` | Filter by sprint status | `all`, `active`, `completed`, `upcoming` |
| `show_team_filter` | `true` | Show team filter dropdown | `true`, `false` |

### Sprint Roadmap Specific Attributes

| Attribute | Default | Description | Options |
|-----------|---------|-------------|---------|
| `view` | `roadmap` | Display format | `roadmap`, `timeline`, `cards` |

**Note:** Marketing and Documentation shortcodes use a simplified single view format focused on their respective task lists.

## Shortcode-Specific Features

### Marketing Shortcode (`[dmtp_sprint_marketing]`)

The marketing shortcode displays:
- **Sprint Name** - Clear sprint identification
- **Sprint Dates** - Start and end dates for planning
- **Marketing Checklist** - Complete task list with:
  - Task names and descriptions
  - Task completion status (Completed/Pending)
  - Verification status (Verified/Not Verified)
  - Visual status indicators with color coding

Perfect for marketing teams to track campaign tasks, content creation, and promotional activities across sprints.

### Documentation Shortcode (`[dmtp_sprint_documentation]`)

The documentation shortcode displays:
- **Sprint Name** - Clear sprint identification  
- **Sprint Dates** - Start and end dates for planning
- **Documentation Checklist** - Complete task list with:
  - Task names and descriptions
  - Task completion status (Completed/Pending)
  - Verification status (Verified/Not Verified)
  - Visual status indicators with color coding

Perfect for documentation teams to track user guides, API documentation, release notes, and other documentation deliverables.

## View Types

### Roadmap View
A detailed vertical timeline showing comprehensive sprint information including:
- Sprint dates and duration
- Team assignments
- Story points (committed vs delivered)
- Velocity percentages
- Demo and retrospective dates
- **Member contributions table** (click to expand)

### Timeline View
A compact horizontal layout perfect for quick overviews:
- Start dates prominently displayed
- Essential metrics at a glance
- **Compact member contributions table** (click to expand)

### Cards View
A responsive grid layout ideal for dashboards:
- Card-based design
- Progress indicators
- Status badges
- **Member contributions table** (click to expand)

## New Feature: Member Contributions Table

Each sprint now includes a detailed member contributions table showing:

### Member Metrics Displayed:
- **Member Name** - Team member name
- **Role** - Member role/designation
- **Est. Hrs** - Estimated hours for the sprint
- **Comm. SP** - Committed story points
- **Del. SP** - Delivered story points  
- **Velocity** - Delivery percentage (color-coded)
- **Hotfixes** - Number of hotfixes
- **Absent** - Absent days during sprint
- **Rating** - Performance rating (color-coded)

### Velocity Color Coding:
- 🟢 **Excellent** (90%+): Green background
- 🔵 **Good** (75-89%): Blue background  
- 🟡 **Average** (50-74%): Yellow background
- 🔴 **Poor** (<50%): Red background

### Rating Color Coding:
- 🟢 **Excellent** (4.5-5.0): Green background
- 🔵 **Good** (3.5-4.4): Blue background
- 🟡 **Average** (2.5-3.4): Yellow background  
- 🔴 **Poor** (<2.5): Red background

### How to Access:
- Click the "👥 View Team Contributions" button in any sprint
- Click again to hide the table
- Works in all three view types (roadmap, timeline, cards)
- Fully responsive design with horizontal scrolling on mobile

## Usage Examples

### Marketing Team Roadmap
```
[dmtp_sprint_roadmap team="Marketing" view="roadmap" count="5" show_team_filter="true"]
```

### Development Timeline
```
[dmtp_sprint_roadmap team="Development" view="timeline" status="active"]
```

### Executive Dashboard
```
[dmtp_sprint_roadmap view="cards" count="8" show_team_filter="true"]
```

### Active Sprints Only
```
[dmtp_sprint_roadmap status="active" view="roadmap"]
```

### Marketing Team Tasks
```
[dmtp_sprint_marketing team="Marketing" count="5"]
```

### Documentation Team Tasks  
```
[dmtp_sprint_documentation team="Documentation" status="active"]
```

### Marketing Tasks with Team Filter
```
[dmtp_sprint_marketing show_team_filter="true" count="8"]
```

### Current Sprint Documentation
```
[dmtp_sprint_documentation status="active" show_team_filter="false"]
```

## Responsive Design

The shortcode is fully responsive and adapts to different screen sizes:
- **Desktop**: Full layout with all features
- **Tablet**: Optimized spacing and font sizes
- **Mobile**: Compact design with horizontal scrolling for member tables

## Permissions

Access to sprint data is controlled by WordPress user roles:
- **Administrator**: Full access
- **DMTP Manager**: Full access
- **DMTP Developer**: Full access
- **DMTP Observer**: Read-only access

## Troubleshooting

### No Sprints Displayed
- Check if sprints exist in the system
- Verify team names match exactly (case-sensitive)
- Ensure user has proper permissions

### Team Filter Not Working
- Verify team names are configured in DMTP settings
- Check that sprints have teams assigned
- Team names must match exactly

### Member Data Not Showing
- Ensure member performance data is entered for the sprint
- Check that ACF fields are properly configured
- Verify member data includes required fields

### Styling Issues
- Check if theme CSS conflicts exist
- Ensure plugin CSS is loaded properly
- Verify responsive viewport meta tag is present

## CSS Customization

You can customize the appearance by targeting these CSS classes:

```css
/* Main containers */
.dmtp-sprint-roadmap { }
.dmtp-roadmap-view { }
.dmtp-timeline-view { }
.dmtp-cards-view { }

/* Member contributions */
.dmtp-toggle-members { }
.dmtp-member-table { }
.dmtp-velocity-excellent { }
.dmtp-rating-good { }
```

## Performance Notes

- Large datasets may affect page load time
- Consider using the `count` attribute to limit results
- Member tables are loaded but hidden by default for performance
- Tables include responsive scrolling for better mobile experience

## ✨ **Interactive Features**

### **Member Contributions Table**
Click the **"👥 View Team Contributions"** button in any sprint to see detailed member performance:
- Member Name, Role, Estimated Hours
- Committed vs Delivered Story Points with velocity percentage
- Hotfixes count, Absent days, Performance rating
- Retrospective notes (when available)

### **Sprint Hotfixes Table**
Click the **"🐛 View Sprint Hotfixes"** or **"🐛 View Hotfixes"** button to see hotfixes data:
- Team-wise hotfix breakdown
- Total hotfixes count per team
- Comprehensive hotfix tracking across sprint teams

### **Marketing Tasks Table**
Click the **"📈 View Marketing Tasks"** or **"📈 Marketing"** button to see marketing task tracking:
- Task name with detailed descriptions
- Task completion status (Completed/Pending)
- Verification status (Verified/Not Verified)
- Visual status indicators with color coding

### **Documentation Tasks Table**
Click the **"📚 View Documentation Tasks"** or **"📚 Documentation"** button to see documentation task tracking:
- Task name with detailed descriptions  
- Task completion status (Completed/Pending)
- Verification status (Verified/Not Verified)
- Visual status indicators with color coding

### **Team Filtering**
When `show_team_filter="true"` is set, users can filter sprints by team using the dropdown.

## Use Cases

### For Marketing Teams
```
[dmtp_sprint_roadmap team="Marketing" view="cards" count="6"]
```
Perfect for showing upcoming marketing sprints with visual progress indicators.

### For Documentation Teams
```
[dmtp_sprint_roadmap team="Documentation" view="timeline" count="8"]
```
Great for showing documentation sprint schedules in a timeline format.

### For Admin Dashboard
```
[dmtp_sprint_roadmap view="roadmap" show_team_filter="true" count="12"]
```
Comprehensive view with all teams and filtering capabilities.

### For Project Status Pages
```
[dmtp_sprint_roadmap status="active" view="cards" show_team_filter="false"]
```
Shows only currently active sprints in an easy-to-scan card layout.

## Troubleshooting

### No Data Displayed
- Ensure you have sprint data in the DMTP plugin
- Check user permissions
- Verify the team name matches exactly

### Styling Issues
- Clear browser cache
- Check for CSS conflicts
- Ensure the plugin's CSS is loading

### Performance Considerations
- Limit the `count` attribute for pages with heavy traffic
- Use specific team filters when possible
- Consider caching if displaying many sprints

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (responsive design)

## Accessibility

- Keyboard navigation support
- Screen reader friendly
- High contrast support
- Focus indicators
- Semantic HTML structure

## Admin Interface Features

### **Sprint Administration Tabs**
The sprint editing interface includes the following tabs:

1. **General** - Basic sprint information (dates, teams)
2. **Teams** - Team member performance tracking
3. **Hotfixes** - Sprint hotfix management and tracking
4. **Notes** - Planning and retrospective notes
5. **Marketing** ✨ *New* - Marketing task tracking with status and verification
6. **Documentation** ✨ *New* - Documentation task tracking with status and verification

### **Marketing Tab Features**
- Add multiple marketing tasks with detailed descriptions
- Track task completion status (checkbox)
- Track verification status (checkbox)
- Tasks display in frontend with visual status indicators

### **Documentation Tab Features**  
- Add multiple documentation tasks with detailed descriptions
- Track task completion status (checkbox)
- Track verification status (checkbox)
- Tasks display in frontend with visual status indicators 