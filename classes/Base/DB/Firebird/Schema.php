<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Copyright © 2011–2013 Spadefoot Team.
 *
 * Unless otherwise noted, LEAP is licensed under the Apache License,
 * Version 2.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License
 * at:
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This class provides a way to access the scheme for a Firebird database.
 *
 * @package Leap
 * @category Firebird
 * @version 2013-01-28
 *
 * @abstract
 */
abstract class Base_DB_Firebird_Schema extends DB_Schema {

	/**
	 * This function returns a result set that contains an array of all fields in
	 * the specified database table/view.
	 *
	 * @access public
	 * @override
	 * @param string $table                 the table/view to evaluated
	 * @param string $like                  a like constraint on the query
	 * @return DB_ResultSet                 an array of fields within the specified
	 *                                      table
	 *
	 * @see http://wiert.wordpress.com/2009/08/13/interbasefirebird-query-to-show-which-fields-in-your-database-are-not-based-on-a-domain/
	 * @see http://wiert.wordpress.com/2009/08/13/interbasefirebird-querying-the-system-tables-to-get-your-actually-used-fieldcolumn-types/
	 * @see http://www.felix-colibri.com/papers/db/interbase/using_interbase_system_tables/using_interbase_system_tables.html
	 * @see http://www.alberton.info/firebird_sql_meta_info.html
	 * @see http://tech.dir.groups.yahoo.com/group/firebird-support/message/94553
	 */
	public function fields($table, $like = '') {
		/*
		$sql = 'SELECT
			TRIM("RDB$RELATION_FIELDS"."RDB$RELATION_NAME") AS "table_name",
			TRIM("RDB$RELATION_FIELDS"."RDB$FIELD_NAME") AS "field_name",
			TRIM(TRIM(CASE "RDB$FIELDS"."RDB$FIELD_TYPE"
				WHEN 17 THEN \'boolean\'
				WHEN 7 THEN \'smallint\'
				WHEN 8 THEN \'integer\'
				WHEN 16 THEN \'int64\'
				WHEN 9 THEN \'quad\'
				WHEN 10 THEN \'float\'
				WHEN 11 THEN \'d_float\'
				WHEN 27 THEN \'double\'
				WHEN 12 THEN \'date\'
				WHEN 13 THEN \'time\'
				WHEN 35 THEN \'timestamp\'
				WHEN 14 THEN \'char\'
				WHEN 37 THEN \'varchar\'
				WHEN 40 THEN \'cstring\'
				WHEN 45 THEN \'blob_id\'
				WHEN 261 THEN \'blob\'
			END)
			|| \' \' ||
			COALESCE(CASE "RDB$FIELDS"."RDB$FIELD_TYPE"
				WHEN 7 THEN
					CASE "RDB$FIELDS"."RDB$FIELD_SUB_TYPE"
						WHEN 1 THEN \'numeric\'
						WHEN 2 THEN \'decimal\'
					END
				WHEN 8 THEN
					CASE "RDB$FIELDS"."RDB$FIELD_SUB_TYPE"
						WHEN 1 THEN \'numeric\'
						WHEN 2 THEN \'decimal\'
					END
				WHEN 16 THEN
					CASE "RDB$FIELDS"."RDB$FIELD_SUB_TYPE"
						WHEN 1 THEN \'numeric\'
						WHEN 2 THEN \'decimal\'
						ELSE \'bigint\'
					END
				WHEN 14 THEN
					CASE "RDB$FIELDS"."RDB$FIELD_SUB_TYPE"
						WHEN 0 THEN \'unspecified\'
						WHEN 1 THEN \'binary\'
						WHEN 3 THEN \'acl\'
						ELSE
						CASE
							WHEN "RDB$FIELDS"."RDB$FIELD_SUB_TYPE" IS NULL THEN \'unspecified\'
						END
					END
				WHEN 37 THEN
					CASE "RDB$FIELDS"."RDB$FIELD_SUB_TYPE"
						WHEN 0 THEN \'unspecified\'
						WHEN 1 THEN \'text\'
						WHEN 3 THEN \'acl\'
						ELSE
						CASE
							WHEN "RDB$FIELDS"."RDB$FIELD_SUB_TYPE" IS NULL THEN \'unspecified\'
						END
					END
				WHEN 261 THEN
					CASE "RDB$FIELDS"."RDB$FIELD_SUB_TYPE"
						WHEN 0 THEN \'unspecified\'
						WHEN 1 THEN \'text\'
						WHEN 2 THEN \'blr\'
						WHEN 3 THEN \'acl\'
						WHEN 4 THEN \'reserved\'
						WHEN 5 THEN \'encoded-meta-data\'
						WHEN 6 THEN \'irregular-finished-multi-db-tx\'
						WHEN 7 THEN \'transactional_description\'
						WHEN 8 THEN \'external_file_description\'
					END
			END, \'\')) AS "type_name",
			COALESCE("RDB$FIELDS"."RDB$NULL_FLAG", 1) AS "nullable",
			SUBSTRING(CAST("RDB$RELATION_FIELDS"."RDB$DEFAULT_SOURCE" AS VARCHAR(255)) FROM 9) AS "default_value",
			COALESCE("RDB$FIELDS"."RDB$CHARACTER_LENGTH", 0) AS "maximum_length",
			ABS(COALESCE("RDB$FIELDS"."RDB$FIELD_SCALE", 0)) AS "decimal_digits",
			"RDB$RELATION_FIELDS"."RDB$FIELD_POSITION" AS "ordinal_position"
		FROM
			"RDB$RELATION_FIELDS"
			JOIN "RDB$FIELDS" ON ("RDB$FIELDS"."RDB$FIELD_NAME" = "RDB$RELATION_FIELDS"."RDB$FIELD_SOURCE")
			LEFT JOIN "RDB$TYPES" ON ("RDB$TYPES"."RDB$TYPE" = "RDB$FIELDS"."RDB$FIELD_TYPE" AND "RDB$TYPES"."RDB$FIELD_NAME" = \'RDB$FIELD_TYPE\')
		WHERE
			"RDB$RELATION_FIELDS"."RDB$SYSTEM_FLAG" = 0
			AND "RDB$RELATION_FIELDS"."RDB$FIELD_SOURCE" LIKE \'RDB$%\'
			AND "RDB$RELATION_FIELDS"."RDB$RELATION_NAME" = \'' . $table . '\'
		ORDER BY
			"RDB$RELATION_FIELDS"."RDB$FIELD_POSITION";';

		// TODO get collation
		// TODO add like condition

		$connection = DB_Connection_Pool::instance()->get_connection($this->data_source);
		$records = $connection->query($sql)->as_array();

		$fields = array();

		foreach ($records as $record) {
			$field = $record['field_name'];

			$fields[$field]['table_name'] = $record['table_name'];
			$fields[$field]['field_name'] = $record['field_name'];

			switch ($record['type_name']) { // e.g. array($date_type, $maximum_length, $decimal_digits, $attributes)
				case 'boolean':
					$type = array('boolean', 0, 0, array());
				break;
				case 'smallint':
					$type = array('integer', 5, 0, array('unsigned' => FALSE, 'range' => array(-'32768', '32767')));
				break;
				case 'smallint numeric':
				case 'smailint decimal':
					$type = array('decimal', 5, abs($record['decimal_digits']), array('unsigned' => FALSE, 'range' => array(-'32768', '32767')));
				break;
				case 'integer':
					$type = array('integer', 10, 0, array('unsigned' => FALSE, 'range' => array('-2147483648', '2147483647')));
				break;
				case 'integer numeric':
				case 'integer decimal':
					$type = array('decimal', 10, abs($record['decimal_digits']), array('unsigned' => FALSE, 'range' => array('-2147483648', '2147483647')));
				break;
				case 'int64 numeric':
				case 'int64 decimal':
					$type = array('decimal', 18, abs($record['decimal_digits']), array());
				break;
				case 'int64 bigint': // http://tracker.firebirdsql.org/browse/CORE-697
				case 'quad':
					$type = array('integer', 18, 0, array('unsigned' => FALSE, 'range' => array('-9223372036854775808', '9223372036854775807')));
				break;
				case 'float': // http://www.janus-software.com/fbmanual/manual.php?book=psql&topic=31
				case 'd_float': // http://www.ibexpert.info/ibe/index.php?n=Doc.DefinitionFLOAT
					$type = array('double', 7, 7, array());
				break;
				case 'double': // http://www.janus-software.com/fbmanual/manual.php?book=psql&topic=31
					$type = array('double', 15, 15, array());
				break;
				case 'date':
					$type = array('date', 10, 0, array());
				break;
				case 'time':
					$type = array('time', 8, 0, array());
				break;
				case 'timestamp':
					$type = array('datetime', 19, 0, array());
				break;
				case 'varchar text':
					$type = array('text', 0, 0, array());
				break;
				case 'varchar':
				case 'varchar unspecified':
				case 'varchar acl':
				case 'cstring':
				case 'char':
				case 'char unspecified':
				case 'char acl':
					$type = array('string', $record['maximum_length'], 0, array());
				break;
				case 'char binary':
					$type = array('binary', $record['maximum_length'], 0, array());
				break;
				case 'blob_id': // http://www.ibexpert.info/ibe/index.php?n=Doc.DefinitionBLOB
					$type = array('integer', 15, 0, array());
				break;
				case 'blob':
				case 'blob unspecified':
				case 'blob text':
				case 'blob blr':
				case 'blob acl':
				case 'blob reserved':
				case 'blob encoded-meta-data':
				case 'blob irregular-finished-multi-db-tx':
				case 'blob transactional_description':
				case 'blob external_file_description':
					$type = array('blob', 0, 0, array());
				break;
				default:
					throw new Throwable_Exception('Message: Unable to map data type. Reason: Case has not yet been handled.');
				break;
			}

			$fields[$field]['actual_type'] = $record['type_name']; // database's data type
			$fields[$field]['type'] = $type[0]; // PHP's data type

			$fields[$field]['maximum_length'] = $type[1];
			$fields[$field]['decimal_digits'] = $type[2];

			$fields[$field]['attributes'] = $type[3];

			$fields[$field]['nullable'] = (bool) $record['nullable'];

			$default_value = $record['default_value'];
			if ($default_value != 'null') {
				switch ($type[0]) {
					case 'boolean':
						settype($default_value, 'boolean');
					break;
					case 'bit':
					case 'integer':
						settype($default_value, 'integer');
					break;
					case 'decimal':
					case 'double':
						settype($default_value, 'double');
					break;
					case 'binary':
					case 'blob':
					case 'date':
					case 'datetime':
					case 'string':
					case 'text':
					case 'time':
						settype($default_value, 'string');
					break;
				}
				$fields[$field]['default_value'] = $default_value;
			}
			else {
				$fields[$field]['default_value'] = NULL;
			}

			$fields[$field]['ordinal_position'] = $record['ordinal_position'];
		}

		return $fields;
		*/
	}

	/**
	 * This function returns a result set of indexes for the specified table.
	 *
	 * +---------------+---------------+------------------------------------------------------------+
	 * | field         | data type     | description                                                |
	 * +---------------+---------------+------------------------------------------------------------+
	 * | schema        | string        | The name of the schema that contains the table.            |
	 * | table         | string        | The name of the table.                                     |
	 * | index         | string        | The name of the index.                                     |
	 * | column        | string        | The name of the column.                                    |
	 * | seq_index     | integer       | The sequence index of the index.                           |
	 * | ordering      | string        | The ordering of the index.                                 |
	 * | unique        | boolean       | Indicates whether index on column is unique.               |
	 * | primary       | boolean       | Indicates whether index on column is a primary key.        |
	 * +---------------+---------------+------------------------------------------------------------+
	 *
	 * @access public
	 * @override
	 * @param string $table                 the table to evaluated
	 * @param string $like                  a like constraint on the query
	 * @return DB_ResultSet                 a result set of indexes for the specified
	 *                                      table
	 *
	 * @see http://www.felix-colibri.com/papers/db/interbase/using_interbase_system_tables/using_interbase_system_tables.html
	 * @see http://www.alberton.info/firebird_sql_meta_info.html
	 */
	public function indexes($table, $like = '') {
		$pathinfo = pathinfo($this->data_source->database);
		$schema = $pathinfo['filename'];

		$builder = DB_SQL::select($this->data_source)
			->column(DB_SQL::expr("'{$schema}'"), 'schema')
			->column(DB_SQL::expr('TRIM("RDB$INDICES"."RDB$RELATION_NAME")'), 'table')
			->column(DB_SQL::expr('TRIM("RDB$INDICES"."RDB$INDEX_NAME")'), 'index')
			->column(DB_SQL::expr('TRIM("RDB$INDEX_SEGMENTS"."RDB$FIELD_NAME")'), 'column')
			->column(DB_SQL::expr('CAST(("RDB$INDEX_SEGMENTS"."RDB$FIELD_POSITION" + 1) AS integer)'), 'seq_index')
			->column(DB_SQL::expr('0'), 'ordering')
			->column(DB_SQL::expr('RDB$INDICES.RDB$UNIQUE_FLAG'), 'unique')
			->column(DB_SQL::expr('IIF("RDB$RELATION_CONSTRAINTS"."RDB$CONSTRAINT_TYPE" = \'PRIMARY KEY\', 1, 0)'), 'primary')
			->from('RDB$INDEX_SEGMENTS')
			->join(DB_SQL_JoinType::_LEFT_, 'RDB$INDICES')
			->on('RDB$INDICES.RDB$INDEX_NAME', DB_SQL_Operator::_EQUAL_TO_, 'RDB$INDEX_SEGMENTS.RDB$INDEX_NAME')
			->join(DB_SQL_JoinType::_LEFT_, 'RDB$RELATION_CONSTRAINTS')
			->where(DB_SQL::expr('COALESCE("RDB$INDICES"."RDB$SYSTEM_FLAG", 0)'), DB_SQL_Operator::_EQUAL_TO_, 0)
			->on('RDB$RELATION_CONSTRAINTS.RDB$INDEX_NAME', DB_SQL_Operator::_EQUAL_TO_, 'RDB$INDICES.RDB$INDEX_NAME')
			->where('RDB$INDICES.RDB$RELATION_NAME', DB_SQL_Operator::_EQUAL_TO_, $table)
			->where('RDB$RELATION_CONSTRAINTS.RDB$CONSTRAINT_TYPE', DB_SQL_Operator::_IS_, NULL)
			->where('RDB$INDICES.RDB$INDEX_INACTIVE', DB_SQL_Operator::_NOT_EQUAL_TO_, 1)
			->order_by(DB_SQL::expr('UPPER("RDB$INDICES"."RDB$RELATION_NAME")'))
			->order_by(DB_SQL::expr('UPPER("RDB$INDICES"."RDB$INDEX_NAME")'))
			->order_by(DB_SQL::expr('CAST(("RDB$INDEX_SEGMENTS"."RDB$FIELD_POSITION" + 1) AS integer)'));

		if ( ! empty($like)) {
			$builder->where(DB_SQL::expr('TRIM("RDB$INDICES"."RDB$INDEX_NAME")'), DB_SQL_Operator::_LIKE_, $like);
		}

		return $builder->query();
	}

	/**
	 * This function returns a result set of database tables.
	 *
	 * +---------------+---------------+------------------------------------------------------------+
	 * | field         | data type     | description                                                |
	 * +---------------+---------------+------------------------------------------------------------+
	 * | schema        | string        | The name of the schema that contains the table.            |
	 * | table         | string        | The name of the table.                                     |
	 * | type          | string        | The type of table.                                         |
	 * +---------------+---------------+------------------------------------------------------------+
	 *
	 * @access public
	 * @override
	 * @param string $like                  a like constraint on the query
	 * @return DB_ResultSet                 a result set of database tables
	 *
	 * @see http://www.firebirdfaq.org/faq174/
	 * @see http://www.alberton.info/firebird_sql_meta_info.html
	 */
	public function tables($like = '') {
		$pathinfo = pathinfo($this->data_source->database);
		$schema = $pathinfo['filename'];

		$builder = DB_SQL::select($this->data_source)
			->column(DB_SQL::expr("'{$schema}'"), 'schema')
			->column(DB_SQL::expr('TRIM("RDB$RELATION_NAME")'), 'table')
			->column(DB_SQL::expr("'BASE'"), 'type')
			->from('RDB$RELATIONS')
			->where(DB_SQL::expr('COALESCE("RDB$SYSTEM_FLAG", 0)'), DB_SQL_Operator::_EQUAL_TO_, 0)
			->where('RDB$VIEW_BLR', DB_SQL_Operator::_IS_, NULL)
			->order_by(DB_SQL::expr('UPPER("RDB$RELATION_NAME")'));

		if ( ! empty($like)) {
			$builder->where(DB_SQL::expr('TRIM("RDB$RELATION_NAME")'), DB_SQL_Operator::_LIKE_, $like);
		}

		return $builder->query();
	}

	/**
	 * This function returns a result set of triggers for the specified table.
	 *
	 * +---------------+---------------+------------------------------------------------------------+
	 * | field         | data type     | description                                                |
	 * +---------------+---------------+------------------------------------------------------------+
	 * | schema        | string        | The name of the schema that contains the table.            |
	 * | table         | string        | The name of the table to which the trigger is defined on.  |
	 * | trigger       | string        | The name of the trigger.                                   |
	 * | event         | string        | 'INSERT', 'DELETE', or 'UPDATE'                            |
	 * | timing        | string        | 'BEFORE', 'AFTER', or 'INSTEAD OF'                         |
	 * | per           | string        | 'ROW', 'STATEMENT', or 'EVENT'                             |
	 * | action        | string        | The action that will be triggered.                         |
	 * | seq_index     | integer       | The sequence index of the trigger.                         |
	 * | created       | date/time     | The date/time of when the trigger was created.             |
	 * +---------------+---------------+------------------------------------------------------------+
	 *
	 * @access public
	 * @override
	 * @param string $table                 the table to evaluated
	 * @param string $like                  a like constraint on the query
	 * @return DB_ResultSet                 a result set of triggers for the specified
	 *                                      table
	 *
	 * @see http://www.alberton.info/firebird_sql_meta_info.html
	 */
	public function triggers($table, $like = '') {
		$pathinfo = pathinfo($this->data_source->database);
		$schema = $pathinfo['filename'];

		$builder = DB_SQL::select($this->data_source)
			->column(DB_SQL::expr("'{$schema}'"), 'schema')
			->column('RDB$RELATION_NAME', 'table')
			->column('RDB$TRIGGER_NAME', 'trigger')
			->column(DB_SQL::expr("CASE 'RDB\$TRIGGER_TYPE' WHEN 1 THEN 'INSERT' WHEN 2 THEN 'INSERT' WHEN 3 THEN 'UPDATE' WHEN 4 THEN 'UPDATE' ELSE 'DELETE' END"), 'event')
			->column(DB_SQL::expr("CASE 'RDB\$TRIGGER_TYPE' & 2 WHEN 0 THEN 'AFTER' ELSE 'BEFORE' END"), 'timing')
			->column(DB_SQL::expr("'ROW'"), 'per')
			->column('RDB$TRIGGER_SOURCE', 'action')
			->column('RDB$TRIGGER_SEQUENCE', 'seq_index')
			->column(DB_SQL::expr('NULL'), 'created')
			->from('RDB$TRIGGERS')
			->where(DB_SQL::expr('COALESCE("RDB$SYSTEM_FLAG", 0)'), DB_SQL_Operator::_EQUAL_TO_, 0)
			->where('RDB$RELATION_NAME', DB_SQL_Operator::_EQUAL_TO_, $table)
			->where('RDB$TRIGGER_INACTIVE', DB_SQL_Operator::_NOT_EQUAL_TO_, 1)
			->order_by(DB_SQL::expr('UPPER("RDB$RELATION_NAME")'))
			->order_by(DB_SQL::expr('UPPER("RDB$TRIGGER_NAME")'))
			->order_by('RDB$TRIGGER_SEQUENCE');

		if ( ! empty($like)) {
			$builder->where(DB_SQL::expr('TRIM("RDB$TRIGGER_NAME")'), DB_SQL_Operator::_LIKE_, $like);
		}

		return $builder->query();
	}

	/**
	 * This function returns a result set of database views.
	 *
	 * +---------------+---------------+------------------------------------------------------------+
	 * | field         | data type     | description                                                |
	 * +---------------+---------------+------------------------------------------------------------+
	 * | schema        | string        | The name of the schema that contains the table.            |
	 * | table         | string        | The name of the table.                                     |
	 * | type          | string        | The type of table.                                         |
	 * +---------------+---------------+------------------------------------------------------------+
	 *
	 * @access public
	 * @override
	 * @param string $like                  a like constraint on the query
	 * @return DB_ResultSet                 a result set of database views
	 *
	 * @see http://www.firebirdfaq.org/faq174/
	 * @see http://www.alberton.info/firebird_sql_meta_info.html
	 */
	public function views($like = '') {
		$pathinfo = pathinfo($this->data_source->database);
		$schema = $pathinfo['filename'];

		$builder = DB_SQL::select($this->data_source)
			->column(DB_SQL::expr("'{$schema}'"), 'schema')
			->column(DB_SQL::expr('TRIM("RDB$RELATION_NAME")'), 'table')
			->column(DB_SQL::expr("'VIEW'"), 'type')
			->from('RDB$RELATIONS')
			->where(DB_SQL::expr('COALESCE("RDB$SYSTEM_FLAG", 0)'), DB_SQL_Operator::_EQUAL_TO_, 0)
			->where('RDB$VIEW_BLR', DB_SQL_Operator::_IS_NOT_, NULL)
			->order_by(DB_SQL::expr('UPPER("RDB$RELATION_NAME")'));

		if ( ! empty($like)) {
			$builder->where(DB_SQL::expr('TRIM("RDB$RELATION_NAME")'), DB_SQL_Operator::_LIKE_, $like);
		}

		return $builder->query();
	}

}
