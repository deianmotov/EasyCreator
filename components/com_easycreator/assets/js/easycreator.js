/**
 * @version SVN: $Id$
 * @package		EasyCreator
 * @subpackage	Frontend-Assets
 * @author		EasyJoomla {@link http://www.easy-joomla.org Easy-Joomla.org}
 * @author		Nikolai Plath {@link http://www.nik-it.de}
 * @author		Created on 24-Sep-08
 */

function drawProject(project)
{
	frm = document.adminForm;
	frm.ebc_project.value=project;
	frm.submit();
}//function