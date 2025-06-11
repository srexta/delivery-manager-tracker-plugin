# DMTP Sprint Roadmap Shortcode Usage Guide

## Overview

The `[dmtp_sprint_roadmap]` shortcode provides a comprehensive view of your team's sprint data in multiple formats.

## Basic Usage

```
[dmtp_sprint_roadmap]
```

This will display all sprints in the default roadmap view.

## Shortcode Attributes

| Attribute | Default | Description | Options |
|-----------|---------|-------------|---------|
| `team` | (all) | Filter sprints by team | Any team name configured in DMTP settings |
| `view` | `roadmap` | Display format | `roadmap`, `timeline`, `cards` |
| `count` | `10` | Number of sprints to display | Any positive integer |
| `status` | `all` | Filter by sprint status | `all`, `active`, `completed`, `upcoming` |
| `show_team_filter` | `false` | Show team filter dropdown | `true`, `false` |

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

## Interactive Features

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